<?

define('MVC_PREFIX', 'Controller_Api');
define('E_OK',             2);
define('E_INPUT_NOJSON',   4);
define('E_INPUT_MISSING',  6);
define('E_INTERNAL',       5);
define('E_AUTH',           7);
define('E_AUTH_APP',       8);
define('E_AUTH_USER',      9);
define('E_OBJECT_MISSING', 10);


Class Api_Controller {

  var $needs_token    = false;
  var $needs_admin    = false;
  var $user           = null;
  var $params         = Array();
  var $session        = null;
  var $response       = null;
  var $request        = null;
    
  static function instance() {
    return new Api_Controller();
  }
  
  function __construct() {
    $this->response = new Api_Response();
  }
  
  static function parse_raw_post() {
  
    $fp = fopen("php://input", "r");

  
    $data_str = '';
    
    while ($data = fread($fp, 1024))
      $data_str .= $data;    
  
    fclose($fp);
    
    Logger::debug("RAW: %o", $data_str);
    //First see if it's JSON
    if ($v = @json_decode($data_str, true)) {
      return $v;
    }
    
    //Guess it's not   
    @parse_str($data_str, $out); 
    return $out;
  
  }
  
  function error ($code, $message = null, $error_num = null) {

    $this->response->code      = (integer) $code;
   // $this->response->error_num = (integer) $code;
    $this->response->message = $message;
    $this->response->status  = 'ERROR';
    Logger::error("Error: %o - %o", $code, $message);
    
    echo $this->response->render();
    exit;

  }
  
  function handle ( $url, $request_method ) {

    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Origin: *");
              
    if (!in_array($request_method, Array('GET', 'POST', 'PUT', 'DELETE'))) {
      $this->error(400, "Request method unknown");  
    }
              
    //Get the the path and the params
    list($url, $params) = Http_Util::split_url($url);
    
    Logger::debug("API - %o %o %o", $request_method, $url, $params);
    //Kill the /
    $url = substr($url,1);
    $arguments = Array();
  
    $this->params = $this->get_params($request_method);

    if (!strlen($url)) {
      $controller = 'index';
    } else {
    
      $method_paths      = explode('/',$url);
      $controller_pieces = Array();
      
      foreach ($method_paths as $method_path) {
        if (is_numeric($method_path) || sizeof($arguments)) {
          $arguments[] = $method_path;
        } else {
          $controller_pieces[] = $method_path;
        }
      }
      
      $controller = implode('_', $controller_pieces);
    }
      
    $method      = strtolower('action_' . $request_method);
    $class       = MVC_PREFIX . '_' . $controller;

    try {
    
      if (!class_exists($class, true)) {
        return $this->error(404, "Method does not exist");
      }    
        
      $success = false;
      $handler = new $class();
      
      
      if (!method_exists($handler, $method)) {
        return $this->error(404,"Method and/or handler not found:" . $request_method);
      }
              
      $handler->request = $this;
      
      if ($handler->needs_token) {
        $handler->user = $handler->check_token();
      }
      
      $r = call_user_func_array( Array($handler, $method), $arguments);
      
      $this->response = $handler->response;
    } catch (Orm_Filter_Exception $e) {
      Logger::error("Error: %o", $e);
      $this->error(400, $e->errors, $e->getCode()); 
    } catch (Api_Exception $e) {
      //Oh no, user made an error
      Logger::error("Error: %o", $e);
      $this->error(400, $e->getMessage(), $e->getCode());
    } catch (User_Exception $e) {
      //Oh no, user made an error
      Logger::error("Error: %o", $e);
      $this->error(400, $e->getMessage(), $e->getCode());
    } catch (Exception $e) {
     
      Logger::error("Error: %o", $e);
      //Ohh crap, it's us.
      if (IS_DEV) {
        $this->error(500, $e->getMessage(), $e->getCode());
      }
      
      //Logger::error($e->getMessage());
      $this->error(500, "Internal error", E_INTERNAL);
    }
      
    echo $this->response->render();
    
    exit;
    
  }
  
  public function remote_ip () {
    
    if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
      list($addr) = explode(',', $_SERVER["HTTP_X_FORWARDED_FOR"]);
      return trim($addr);
    }
  
    return $_SERVER["REMOTE_ADDR"];
  }
  
  public function get_params ($request_method) {
  
    switch ($request_method) {
      case 'GET':
        return $_GET;
      case 'POST':
        if (count($_POST)) {
          return $_POST; 
        }
        Logger::debug("GOT NONE");
        return $this->parse_raw_post();
      case 'DELETE':
         return [];
      case 'PUT': //Stupid PHP
/*         Logger::debug("%o", $_POST); */
        return $this->parse_raw_post();
      default:
        throw new Api_Exception('Invalid method: %o', $request_method);
    }
  
  }
  
  public function request_param ($var, $html_decode = false) {
  
    if (!isset($this->params[$var])) {
      return null;
    }
    
    $val = $this->params[$var];
    
    return $html_decode?Sanitize::decode_html($val):$val;
  }
  
  function check_token ($is_optional = false) {

    $user = $this->get_user_from_auth_token();
      
    if (!$user) {
      if ($is_optional) {
        return;
      }
      throw new Api_Exception('Unauthorized');
    }
    
    return $this->user = $user;
    
  }
  
  public function get_user_from_auth_token() {
  
    $headers = getallheaders();    

    if (!isset($headers['X-Auth-Token'])) {
      return null;
    }
    
    $token        = $headers['X-Auth-Token'];
    
    if (strlen($token) != 23) {
      $cookie_token = Cookie::decode(SESSION_COOKIE,  urldecode($token));
    } else {
      $cookie_token = $token;  
    }
    
    if (empty($cookie_token)) {
      return;
    }    

    $user = Orm('user')->by_session_token($cookie_token);
    
    if (!$user || !$user->loaded()) {
      return null;
    }
    
    return $user; 
  }
  
  function param($field, $default = null, $filter = null, $options = null) {
    
    $value = $this->request_param($field);
    if (!strlen($value)) {
      $value = null;
    }
    return Sanitize::filter($field, $value, $default, $filter, $options);
    
  }
    
  public function geoip() {
     return geoip_record_by_name ($this->remote_ip());
  }
}
