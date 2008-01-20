<?# $Id$ ?>
<?

   class DatabaseConnection extends Object
   {
      static private $connections;

      private $name;
      private $connection;

      static function load($name) {
         if ($connection = self::$connections[$name]) {
            return $connection;
         } elseif ($options = $GLOBALS['_DATABASE'][$name]) {
            while (is_string($options)) {
               $options = $GLOBALS['_DATABASE'][$options];
            }
            return self::$connections[$name] = new DatabaseConnection($name, $options);
         } else {
            raise("Unconfigured database '$name'");
         }
      }

      function __construct($name, $options) {
         $this->name = $name;
         $this->connection = new PDO(
            $options['dsn'], $options['username'], $options['password']
         );
         $this->connection->setAttribute(
            PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION
         );
      }

      function get_name() {
         return $this->name;
      }

      function query($sql, $params=null) {
         if (!is_array($params)) {
            $params = array_slice(func_get_args(), 1);
         }

         if (config('log_level') >= LOG_DEBUG) {
            $args = $params;
            array_unshift($args, str_replace('?', '%s', $sql));
            log_debug("Database query: [{$this->name}] '".call_user_func_array(sprintf, $args)."'");
         }

         $stmt = $this->connection->prepare(
            $sql, array(PDO::ATTR_STATEMENT_CLASS => array(DatabaseStatement))
         );
         $stmt->setFetchMode(PDO::FETCH_ASSOC);
         return $stmt->execute($params);
      }

      function insert_id() {
         return $this->connection->lastInsertId();
      }
   }

   class DatabaseStatement extends PDOStatement
   {
      function execute($params=array()) {
         parent::execute($params);
         return $this;
      }

      function fetch_all() {
         return parent::fetchAll();
      }

      function fetch_column($column=0) {
         return parent::fetchColumn($column);
      }

      function fetch_load($class) {
         $class = get_class($class);
         if ($data = $this->fetch()) {
            $object = new $class();
            $object->load($data);
            return $object;
         }
      }

      function fetch_all_load($class) {
         $class = get_class($class);
         $objects = array();
         while ($data = $this->fetch()) {
            $object = new $class();
            $object->load($data);
            $objects[] = $object;
         }
         return $objects;
      }
   }

?>
