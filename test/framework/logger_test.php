<?# $Id$ ?>
<?

   class LoggerTest extends TestCase
   {
      function setup() {
         $this->file = new Tempfile();
         $this->path = $this->file->path;
         $this->logger = $GLOBALS['_LOGGER'];
         $this->logger->file = $this->path;
         $this->logger->level = LOG_INFO;
      }

      function teardown() {
         $this->logger->level = LOG_DISABLED;
      }

      function test_constants() {
         $this->assertTrue(defined('LOG_DISABLED'));
         $this->assertTrue(defined('LOG_ERROR'));
         $this->assertTrue(defined('LOG_WARN'));
         $this->assertTrue(defined('LOG_INFO'));
         $this->assertTrue(defined('LOG_DEBUG'));
      }

      function test_construct() {
         $this->assertIsA($this->logger, Logger);
         $this->assertEqual($this->path, $this->logger->file);
         $this->assertEqual(LOG_INFO, $this->logger->level);
      }

      function test_log() {
         $this->logger->log("foo");
         $this->assertFileContents("foo", $this->path);
         $this->logger->log("bar");
         $this->assertFileContents("foo\nbar", $this->path);
      }

      function test_log_with_higher_level() {
         $this->logger->log("foo");
         $this->logger->log("bar", LOG_DEBUG);
         $this->assertFileContents("foo", $this->path);
      }

      function test_log_with_lower_level() {
         $this->logger->log("foo");
         $this->logger->log("bar", LOG_ERROR);
         $this->assertFileContents("foo\nbar", $this->path);
      }

      function test_log_with_invalid_file() {
         $this->logger = new Logger('/this/path/aint/there/i/say');
         ob_start();
         $this->assertFalse($this->logger->log('foo'));
         $output = ob_get_clean();
         $this->assertEqual(LOG_DISABLED, $this->logger->level);
         $this->assertEqual('<p><b>Warning:</b> the logfile <tt>/this/path/aint/there/i/say</tt> is not writable</p>.', $output);
      }

      function test_log_wrappers() {
         $this->logger->level = LOG_DEBUG;
         log_error('1');
         log_warn('2');
         log_info('3');
         log_debug('4');

         $this->assertFileContents("1\n2\n3\n4", $this->path);
      }
   }

?>
