<?

Class Controller_Www_User extends Controller_HTTPS {
      
  function action_connect() {
    
    $facebook = new Facebook_Api;        
   
    if (!$access_token = $facebook->getAccessToken()) {
      if ($this->request->query('connect')) {
        return $this->redir('/u/login');
      }
      $redirect_uri = 'https://'. BASE_DOMAIN . '/u/connectfb?connect=1';
      $login_url = $facebook->getLoginUrl(['redirect_uri'=>$redirect_uri, 'scope'=>FACEBOOK_APP_PERMS]);
      return $this->redir($login_url);
    }
    
    // We have an access token, let's log them in.
        
    try {
    
      $user = Orm('user')->create_from_fb_user_id($access_token);
      
      if (!$user->loaded()) {
        $this->message('Error connecting with facebook','error');
        return $this->redir('/u/login');
      }
      
      $this->login($user);
      
    } catch (Exception $e) {
      Logger::error("Error connecting with facebook:%o", $e);
      if (!$this->request->query('connect')) {
        $redirect_uri = 'https://'. BASE_DOMAIN . '/u/connectfb?connect=1';
        $login_url = $facebook->getLoginUrl(['redirect_uri'=>$redirect_uri, 'scope'=>FACEBOOK_APP_PERMS]);
        return $this->redir($login_url);
      }
    }
    
    return $this->redir();
    
  }
  
}