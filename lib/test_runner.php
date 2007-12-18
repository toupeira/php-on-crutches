<?# $Id$ ?>
<?

   require_once 'simpletest/unit_tester.php';
   require_once 'simpletest/reporter.php';

   class TestRunner extends Object
   {
      private $suite;

      function __construct($message=null) {
         $this->suite = new GroupTest($message);
      }

      function add_dirs($dirs) {
         foreach ((array) $dirs as $dir) {
            foreach (glob(TEST.basename($dir)."/*.php") as $file) {
               $this->suite->addTestFile($file);
            }
         }
      }

      function run($reporter=null) {
         $reporter = any($reporter, new TextReporter());
         print "\n";
         $this->suite->run($reporter);
         print "\n";
      }
   }

?>
