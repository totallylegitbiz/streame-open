<? 



define('IS_CLI',php_sapi_name() === 'cli');

define('APP_PROJECT','Strea.me');

define('KOHANA_START_TIME',   microtime(TRUE));
define('START_MTS',           KOHANA_START_TIME);
define('KOHANA_START_MEMORY', memory_get_usage());

#YEAH WE LIVE ON THE EDGE
ignore_user_abort(true);
set_time_limit(0);

if (!defined('APP_NAME')) {
  define('APP_NAME','console');
}

define('APP_BASE_NAME', 'streame');

//Autoloader for the old schoool way.
spl_autoload_register(function ($class) {
  $class = strtolower($class);
  
  $file = CLASS_BASE . '/' . str_replace('_', '/', $class) . '.php';
  if (!file_exists($file)) {
    return;
  }
  include($file);
  
});

//Locale
date_default_timezone_set('America/New_York');
setlocale(LC_ALL, 'en_US.utf-8');

//Redis database config
define('REDIS_CACHE_DB',    0);
define('REDIS_SESSION_DB',  1);
define('REDIS_COUNTERS_DB', 2);
define('REDIS_PUBSUB_DB',   3);
define('REDIS_PCACHE_DB',   4);
define('REDIS_BLOCK_DB',    5);
define('REDIS_DATA_DB',     6); //Used to persistant, long term data

define('SYSLOG_NAME',       APP_BASE_NAME . '-www');

//File locations
define('APP_BASE', realpath(__DIR__ . '/..'));
define('VAR_BASE', '/var/lib/' . APP_BASE_NAME);
define('EXT', '.php');
if(!defined('DOCROOT')) { define('DOCROOT', '/'); }; //Duummmbbb

//Directories
define('APPPATH', APP_BASE . '/application/');
define('MODPATH', APP_BASE . '/modules/');
define('SYSPATH', APP_BASE . '/system/');
define('CLASS_BASE', APPPATH . '/classes');
define('TPL_COMPILE_DIR', VAR_BASE . '/templates_c'); 
define('LOGBASE', VAR_BASE . '/logs');
define('IMAGE_STORE_DIR', VAR_BASE . '/images');

// Load the core Kohana class
require SYSPATH.'classes/Kohana/Core'.EXT;
require SYSPATH.'classes/Kohana'.EXT;

spl_autoload_register(array('Kohana', 'auto_load'));
ini_set('unserialize_callback_func', 'spl_autoload_call');

// Settings, super ghetto way to check if in prod or not



if (strpos(gethostname(),'.prod.')) {
  require APP_BASE . '/application/settings/prod.php';
} else {
  require APP_BASE . '/application/settings/dev.php';
}


//Set the default language
I18n::lang('en-us');

/**
 * Set Kohana::$environment if a 'KOHANA_ENV' environment variable has been supplied.
 *
 * Note: If you supply an invalid environment name, a PHP warning will be thrown
 * saying "Couldn't find constant Kohana::<INVALID_ENV_NAME>"
 */
 
if (isset($_SERVER['KOHANA_ENV']))
{
  Kohana::$environment = constant('Kohana::'.strtoupper($_SERVER['KOHANA_ENV']));
}


/**
 * Initialize Kohana, setting the default options.
 *
 * The following options are available:
 *
 * - string   base_url    path, and optionally domain, of your application   NULL
 * - string   index_file  name of your index file, usually "index.php"       index.php
 * - string   charset     internal character set used for input and output   utf-8
 * - string   cache_dir   set the internal cache directory                   APPPATH/cache
 * - boolean  errors      enable or disable error handling                   TRUE
 * - boolean  profile     enable or disable internal profiling               TRUE
 * - boolean  caching     enable or disable internal caching                 FALSE
 */
 
Kohana::init(array(
  'base_url'   => '/',  
  'cache_dir'  => VAR_BASE . '/cache',
  'errors'     => !(!IS_DEV || APP_NAME == 'api') && PHP_SAPI != 'cli'
));

if (IS_DEV) {
/*   error_reporting(-1); */
  error_reporting(E_ALL ^ E_DEPRECATED);
}

Kohana::$environment = IS_DEV?Kohana::DEVELOPMENT:Kohana::PRODUCTION;
Kohana::$config->attach(new Config_File);

//We do not use Kohanas lame ass logging system
//Kohana::$log->attach(new Log_File(VAR_BASE . '/logs'));

Kohana::modules(array(
  // 'auth'       => MODPATH.'auth',       // Basic authentication
  // 'cache'      => MODPATH.'cache',      // Caching with multiple backends
  // 'codebench'  => MODPATH.'codebench',  // Benchmarking tool
  'database'   => MODPATH.'database',   // Database access
  // 'image'      => MODPATH.'image',      // Image manipulation
  'minion'     => MODPATH.'minion',     // CLI Tasks
  'orm'        => MODPATH.'orm',        // Object Relationship Mapping
  // 'unittest'   => MODPATH.'unittest',   // Unit testing
  // 'userguide'  => MODPATH.'userguide',  // User guide and API documentation
  ));


//Salting and Session backends.
Cookie::$salt     = 'spark them pets you bastards';

if (IS_DEV) {
  Cookie::$salt     .= '::' . BASE_DOMAIN;
}

Cookie::$domain   = '.' . BASE_DOMAIN;
Session::$default = 'database';

include 'global.php';
include 'loader.php';
include APPPATH . '../vendor/autoload.php';

//Dwoooo
require APPPATH    . '/dwoo.php'; //Dwoo Specific functions

require CLASS_BASE . '/predis.php'; 
require CLASS_BASE . '/OAuth.php'; 
/* require CLASS_BASE . '/Controller.php';  */

require CLASS_BASE . '/sendgrid/SendGrid_loader.php';  
#require CLASS_BASE . '/htmlpurifier/HTMLPurifier.includes.php'; 
/* require CLASS_BASE . '/Services/Twilio.php';  */

require CLASS_BASE . '/PredictionIO/include_all.php';

//This is a second autoloader for classes like the Aws one.
/*
spl_autoload_register(function ($class) {

  $file = CLASS_BASE . '/' . str_replace('\\', '/', $class) . '.php';

  if (!file_exists($file)) {
    return;
  }
  include($file);
  
});
*/


Newrelic::set_app_name();
//Newrelic::background_job(IS_CLI);
Newrelic::background_job(false);

if (APP_NAME == 'api') {
  register_shutdown_function(function () {
    $error = error_get_last();
    if( $error !== NULL && $error['type'] == 1) {
      Logger::error("FATAL ERROR: %o", $error);
    }
  });
}








