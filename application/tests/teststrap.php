<?

require realpath(__DIR__) .'/../bootstrap.php';

define('RUNNING_TEST', true);
define('LOG_DELAY', false);

Class TestCase extends PHPUnit_Framework_TestCase {

  function get_user() {
    return Orm('user')->randomize()->find();  
  }
  
  function generate_user() {
    $user = Orm('user');
    $user->email = microtime(true) . '@email.com';
    $user->save();
    
    return $user;  
  }
  
}



require CLASS_BASE . '/SimpleTest/browser.php';


