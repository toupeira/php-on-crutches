<?

   # Set the current environment (production, development, or test)
   #$_SERVER['ENVIRONMENT'] = 'development';

   # Framework directories
   define('ROOT',         dirname(dirname(__FILE__)).'/');
   define('APP',          ROOT.'app/');
   define('CONFIG',       ROOT.'config/');
   define('DB',           ROOT.'db/');
   define('LANG',         ROOT.'lang/');
   define('LIB',          ROOT.'lib/');
   define('LOG',          ROOT.'log/');
   define('TEST',         ROOT.'test/');
   define('TMP',          ROOT.'tmp/');

   # Application directories
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
