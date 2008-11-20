<?# $Id$ ?>
<?

   class QuerySetTest extends TestCase
   {
      function setup() {
         $this->query = new MockQuerySet(new MockModelMapper);
      }

      function assertSql($sql, $params=array()) {
         $this->assertEqual($sql, $this->query->sql);
         $this->assertEqual($params, $this->query->params);
      }

      function test_get_sql() {
         $this->assertSql('SELECT `table`.* FROM `table`');

         $this->query->select('foo');
         $this->assertSql('SELECT `table`.*, foo FROM `table`');
      }
   }

?>
