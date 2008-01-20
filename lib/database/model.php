<?# $Id$ ?>
<?

   abstract class DatabaseModel extends Model
   {
      static protected $table_attributes;

      protected $database = 'default';
      protected $connection;

      protected $table;
      protected $primary_key = 'id';
      protected $load_attributes = true;

      function __construct() {
         if (empty($this->table)) {
            raise("No table set for model '".get_class($this)."'");
         } elseif (empty($this->database)) {
            raise("No database set for model '".get_class($this)."'");
         }

         if ($this->load_attributes and empty($this->attributes)) {
            if (!($this->attributes = self::$table_attributes[$this->table])) {
               $this->attributes = self::$table_attributes[$this->table] = array_pluck(
                  $this->query("DESCRIBE {$this->table}")->fetchAll(),
                  'Field');
            }
         }

         $args = func_get_args();
         call_user_func_array(array($this, 'parent::__construct'), $args);
      }

      static function find() { raise("Sorry, but PHP won't let me do this"); }
      static function find_all() { raise("Sorry, but PHP won't let me do this"); }

      function get_connection() {
         if (!$this->connection) {
            $this->connection = DatabaseConnection::load($this->database);
         }

         return $this->connection;
      }

      function _find($id) {
         return $this->query(
            "SELECT * FROM {$this->table} WHERE {$this->primary_key} = ? LIMIT 1", $id
         )->fetch_construct($this);
      }

      function _find_all() {
         return $this->query(
            "SELECT * FROM {$this->table}"
         )->fetch_all_construct($this);
      }

      function exists() {
         return !is_null($this->id);
      }

      function save() {
         if (!$this->is_valid()) {
            return false;
         }

         $fields = array();
         $params = array();

         if ($this->exists()) {
            $action = 'update';

            foreach ($this->attributes as $key) {
               $fields[] = "`$key` = ?";
               $params[] = $this->data[$key];
            }

            $query = "UPDATE %s SET %s WHERE {$this->primary_key} = ?";
            $params[] = $this->id;
         } else {
            $action = 'create';

            foreach ($this->attributes as $key) {
               $fields[] = '?';
               $params[] = $this->data[$key];
            }

            $query = "INSERT INTO %s VALUES (%s)";
         }

         $this->call_if_defined(before_save);
         $this->call_if_defined("before_$action");

         $query = sprintf($query, $this->table, implode(", ", $fields));
         array_unshift($params, $query);
         call_user_func_array(array($this, query), $params);

         $this->call_if_defined("after_$action");
         $this->call_if_defined(after_save);

         return true;
      }

      function destroy() {
         if ($this->exists()) {
            $this->call_if_defined(before_destroy);

            $this->query(
               "DELETE FROM {$this->table} WHERE {$this->primary_key} = ?",
               $this->id
            );

            $this->call_if_defined(after_destroy);

            return true;
         } else {
            return false;
         }
      }

      protected function query() {
         $args = func_get_args();
         return call_user_func_array(array($this->get_connection(), query), $args);
      }
   }

?>
