<?

Class Controller_Api_Session extends Api_Controller {

  function action_get() {  
    $user = $this->check_token();
    $this->response->set(Array('user'=>$user));
  }
  
  function action_put() { # LOGIN

    $fb_token     = $this->request->param('fb_token', null, FILTER_VALIDATE_STR);
    $email        = $this->request->param('email',    '', FILTER_VALIDATE_EMAIL); 
    $password     = $this->request->param('password', '', FILTER_VALIDATE_STR);
    $cookie       = (bool) $this->request->param('cookie', false);
    
    if ($fb_token && $email) {
      throw new Api_Exception('Please only have an email or a token');
    }
    
    if ($fb_token) { 
      $user = Orm('user')->create_from_fb_token($fb_token);
    } else {
    
      if (!$user = Orm('user')->by_login($email, $password)) {
        throw new Api_Exception('Invalid user');
      }
    }
    
    if ($cookie) {
      Session::instance()->set('user_id', $user->id);
      $this->response->payload = Array('user'=>$user);
    } else {
      $this->response->payload = Array('token'=>$user->generate_session(), 'user'=>$user);
    }
  }
  
  function action_post() { # REGISTER

    $email        = $this->request->param('email',        null, FILTER_VALIDATE_STR_NOT_EMPTY); 
    $password     = $this->request->param('password',     null, FILTER_VALIDATE_STR_NOT_EMPTY);
    $display_name = $this->request->param('display_name', null, FILTER_VALIDATE_STR_NOT_EMPTY);
    $cookie       = $this->request->param('cookie');
     
    $user         = Orm('user')->register($email, $password, $display_name);   
    
    if ($cookie) {
      Session::instance()->set('user_id', $user->id);
      $this->response->payload = Array('token'=>$user->generate_session(), 'user'=>$user);
      return;
    }
    
    $this->response->payload = Array('token'=>$user->generate_session(), 'user'=>$user);
     
  }
  
  function action_delete() {
    $user = $this->check_token();
    Session::instance()->set('user_id', null);
  }
  
}