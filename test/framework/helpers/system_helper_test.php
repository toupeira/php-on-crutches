<?# $Id$ ?>
<?

   class SystemHelperTest extends TestCase
   {
      function test_run() {
         $this->assertTrue(run('true'));
         $this->assertFalse(run('false'));
      }

      function test_mktemp() {
         $file = mktemp();
         $this->assertTrue(is_file($file));
         $this->assertMatch('#^/tmp/'.config('name').'\.\w{6}#', $file);

         $dir = mktemp(true);
         $this->assertTrue(is_dir($dir));
         $this->assertMatch('#^/tmp/'.config('name').'\.\w{6}#', $file);
      }

      function test_rm_f() {
         $this->assertNull(rm_f('/tmp/invalid/file'));
         $file = mktemp();
         $this->assertTrue(rm_f($file));
         $this->assertFalse(is_file($file));
      }
   }

?>
