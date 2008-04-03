<?# $Id$ ?>
<?

   # Default paths
   define('ROOT',         dirname(dirname(__FILE__)).'/');
   define('ROOT_NAME',    basename(ROOT));

   define('APP',          ROOT.'app/');
   define('LIB',          ROOT.'lib/');
   define('DB',           ROOT.'db/');
   define('CONFIG',       ROOT.'config/');
   define('LOG',          ROOT.'log/');
   define('TEST',         ROOT.'test/');
   define('TMP',          ROOT.'tmp/');

   # Application paths
   define('CONTROLLERS',  APP.'controllers/');
   define('MODELS',       APP.'models/');
   define('VIEWS',        APP.'views/');
   define('HELPERS',      APP.'helpers/');
   define('FIXTURES',     TEST.'fixtures/');

   # The website root
   define('WEBROOT',      ROOT.'public/');

   # Asset paths (used in URLs)
   define('IMAGES',       'images/');
   define('STYLESHEETS',  'stylesheets/');
   define('JAVASCRIPTS',  'javascripts/');

   # Load the framework
   require LIB.'init.php';

?>
