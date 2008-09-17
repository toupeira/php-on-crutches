<?# $Id$ ?>
<?

   class SystemTest extends TestCase
   {
      function test_run() {
         $this->assertTrue(run('true'));
         $this->assertFalse(run('false'));
      }

      function test_tempfile() {
         $file = new Tempfile();
         $this->assertTrue($file->exists());

         $path = $file->path;
         $this->assertTrue(is_file($path));
         $this->assertMatch('#^/tmp/'.config('name').'\.\w{6}#', $path);

         $file->write('foo bar');
         $this->assertEqual('foo bar', $file->read());

         $file = null;
         $this->assertFalse(is_file($path));
      }

      function test_tempfile_destroy() {
         $file = new Tempfile();
         $this->assertTrue($file->exists());

         $file->destroy();
         $this->assertFalse($file->exists());
         $this->assertFalse(is_file($file->path));
      }

      function test_tempdir() {
         $dir = new Tempdir();
         $this->assertTrue($dir->exists());

         $path = $dir->path;
         $this->assertTrue(is_dir($path));
         $this->assertMatch('#^/tmp/'.config('name').'\.\w{6}#', $path);

         touch("$path/foobar");
         $this->assertTrue(is_file("$path/foobar"));

         $dir = null;
         $this->assertFalse(is_dir($path));
      }

      function test_rm_f() {
         $this->assertNull(rm_f('/tmp/invalid/file'));
         $file = new Tempfile();
         $this->assertTrue(rm_f($file->path));
         $this->assertFalse(is_file($file->path));
      }
   }

?>
