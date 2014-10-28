<?

define('DOCROOT', __DIR__);
define('APP_NAME', 'api');
include '../bootstrap.php';

try {

  // Remap /api to /
  $_SERVER["REQUEST_URI"] = preg_replace('/^\/api/', '', $_SERVER["REQUEST_URI"]);

  if ($_SERVER["REQUEST_URI"] == '') {
    $_SERVER["REQUEST_URI"] = '/';
  }

  Newrelic::name_transaction($_SERVER["REQUEST_METHOD"] . ':' . Sanitize::strip_after($_SERVER["REQUEST_URI"],'?'));
  Newrelic::capture_params();
  
  Api_Controller::instance()->handle($_SERVER["REQUEST_URI"], $_SERVER["REQUEST_METHOD"]);

} catch (Exception $e) {
  Logger::error("Exception: %o", $e);
  if (IS_DEV) {
    throw $e;
  }
}