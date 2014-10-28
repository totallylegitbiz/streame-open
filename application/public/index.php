<?


try {

  define('DOCROOT', __DIR__);
  
  
  $app = 'www';
  
  if (preg_match('/^(admin|static)\./', $_SERVER['HTTP_HOST'], $matches)) {  
    $app = $matches[1];    
  }
  
  define('APP_NAME', $app);
  
  
  include '../bootstrap.php';  
  include '../routes/' . $app . '.php';
  
  if (
       (
       (IS_DEV && APP_NAME != 'static')
     ||
       (APP_NAME == 'portal')
       ) && (APP_NAME != 'util')
      ) {
  }
  
  $result =  Request::factory()
    ->execute()
    ->send_headers()
    ->body();
    
} catch (HTTP_Exception_404 $e) {
    $result = Request::factory('error/404')
    ->execute()
    ->send_headers()
    ->body();
} catch (Exception $e) {
 
  header("HTTP/1.1 500 Internal Server Error");
  Logger::error("Exception: %o", $e->getMessage());
  
  if (IS_DEV) {
    throw $e;
  }
  
  $result = Request::factory('error/500')
    ->execute()
    ->body();

}

$footer_data = "<!-- Handled by " . hostname() . " in " . round((microtime(true) - START_MTS) * 1000) . "ms at " . date('c') . " in revision " . App::rev() . " -->";
echo str_replace('</html>', $footer_data . "\n</html>" , $result);
