<?


Class Controller extends Kohana_Controller {

  var $template;
  var $session;
  var $user = null;
  var $assigned_vars = Array();
  var $needs_login = false;
  var $needs_admin = false;
  var $no_session  = false;
  var $needs_scheme = null; 
  
  public function __construct(Request $request, Response $response) {
  
    $this->template   = new Template();
		$this->request    = $request;
		$this->response   = $response;
		
		if (!$this->no_session) {
      //Set up the session  
      $this->session = Session::instance();
     
      if ($user_id = $this->session->get('user_id')) {
        $user = Orm('user', $user_id);
        if ($user->loaded()) {
          $this->user = $user;
        } else {
          $this->logout();
        }
      }
    }
    
    if ($this->needs_login || $this->needs_admin) {  
      $this->needs_login($this->needs_admin);      
    }
   		
    if ($this->needs_scheme == 'https' && !$this->is_https()) {
      return $this->redir($this->url('https'));
    }
    if ($this->needs_scheme == 'http' && $this->is_https()) {
      return $this->redir($this->url('http'));
    }
    
  }
  
  public function needs_login( $needs_admin = false ) {

    if (!$this->user) {
      $this->message('Please login');
  
      return $this->redir($this->login_url(), 'www', $this->url());
    }
    
    if ($needs_admin && $this->user->permission != 'ADMIN') {
      $this->message('Admins only','error');
      return $this->redir($this->logout_url());
    }
  
  }
  
  public function is_https() {
    return $_SERVER["HTTPS"];
  }
  
  function remote_ip() {
    if (php_sapi_name() == 'cli') 
  	  return;
    return ifset($_SERVER["HTTP_X_FORWARDED_FOR"],$_SERVER['REMOTE_ADDR']);
  }

  public function url($scheme = 'https') {
  
    if (!$scheme) {
      $scheme = 'http' . ($_SERVER["HTTPS"]?'s':'');
    }
    return $scheme . '://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
  }
  
  public function login_url() {
    return 'https://' . BASE_DOMAIN . '/u/login';
  }
  public function logout_url() {
    return 'https://' . BASE_DOMAIN . '/u/logout';
  }
  
  public function assign($var, $value) {
    $this->assigned_vars[$var] = $value;
  }
  
  public function render ($template_file) {
    $this->assign('_user', $this->user);
    if ($this->user) {
      $this->assign('_push_token', $this->session->get('push_token'));
    } else {
      $this->assign('_push_token',null);
    }
   
   if (!$this->no_session) {
      if ($messages = $this->session->get('messages')) {
        $this->assign('_messages', $messages);
      } else {
        $this->assign('_messages', []);
      }
      //Blank it out no matter what
      $this->session->set('messages', Array());
    }
    
    /*
    $pieces = explode('_', strtolower(get_class($this)));
    $last   = end($pieces); 
    
    if ($last)  { 
      $this->template->addPath(APP_BASE.'/application/apps/'.APP_NAME.'/views/' .$last );
    }
    */
    
    $this->response->body($this->template->get($template_file,$this->assigned_vars));
  }
  
  public function login (Model_User $user) {
    if ($user->loaded()) {
      $this->session->set('user_id', $user->id);
      #$this->session->set('push_token', uniqid('push_', true));
    }
  }
  
  public function logout() {
    $this->session->delete('user_id');
    
  }
  
  public static function redirect($uri = '', $code = 302) {
    die('DONT CALL ME: ' . __FILE__ . ':' . __LINE__);
  }
	
  public function redir ( $url = null, $app_name = null, $last_url = null, $safe = true) {

    if (strlen($last_url)) {
      $this->session->set('last_url', $last_url);
    }
    
    if (!$url) {
    
      if (!$url = $this->session->get('last_url')) {
        $url = '/';
      }
      
      $this->session->set('last_url', null);
      
    }
    
    if (!preg_match('/^http[s]?:/', $url)) {
      if ($app_name) {
        $url = 'https://' . $app_name . '.' . BASE_DOMAIN . $url;
      }
    }
    Logger::debug('Redirecting to: %s Last: %s',$url, $last_url);
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $url);
    exit;
  }
  
  public function from_redirect($default = '/') {
  
    if (!$url = $this->session->get('last_url')) {
      $url = $default;
    }
    
    return $this->redir($url);
    
  }
  
  public function message($body, $type = 'info', $target=null) {
    $messages = $this->session->get('messages');
    
    if (!is_array($messages)) {
      $messages = Array();
    }
    
    $messages[$body] = Array('body'=>$body,'type'=>$type,'target'=>$target);
    
    $this->session->set('messages', $messages);
    
  }
  
  public function geoip() {
     return geoip_record_by_name ($this->remote_ip());
  }
  
}

Class Controller_HTTPS extends Controller {
  var $needs_scheme = 'https';
}

Class Controller_HTTP extends Controller {
  var $needs_scheme = 'http';
}
