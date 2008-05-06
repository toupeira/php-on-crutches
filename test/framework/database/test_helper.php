<?

   require_once LIB.'database/base.php';

   class MockModelMapper extends DatabaseMapper
   {
      public $sql;
      public $args;
      public $calls;

      protected $_table = 'table';

      function execute($sql, $args=null) {
         $this->sql = $sql;
         $this->args = $args;
         return $this;
      }

      function build_condition() {
         $args = func_get_args();
         list($this->sql, $this->args) = call_user_func_array(array(parent, build_condition), $args);
         return array($this->sql, $this->args);
      }

      function __call($method, $args) {
         $this->calls[] = array($method, $args);
      }
   }

   class MockQuerySet extends QuerySet
   {
   }

   class MockModel extends ActiveRecord
   {
   }

?>
