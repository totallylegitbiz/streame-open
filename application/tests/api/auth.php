<?


class Api_Auth_TestCase extends TestCase {

  public function test_ping() {
  
    $client = new Api_Client();
    $r = $client->get('ping');
    $this->assertEquals('PONG', $r->response['data']);  
    
  }  
  
  public function test_register() {
    
    $client = new Api_Client();
    
    $data = Array(
      'email'         => sha1(microtime()) . '@woo.ly',
      'password'      => sha1(microtime()),
      'name'          => 'AMAZING NAME'
    );
    
    $r    = $client->post('session', $data);
    $user = $r->response['data']['user'];
   
    $this->assertEquals($data['email'], $user['email']);
    $this->assertEquals($data['name'],  $user['name']);
    
    $r     =  $client->put('session', Array('email' => $data['email'], 'password' => $data['password']));
    $token =  $r->response['data']['token'];

    //First let's test if we're able to pull the user using the session
    $user = Orm('user')->by_session_token($token);
    $this->assertTrue($user != null && $user->loaded());
    
    $r   = $client->get('session', [], ['X-Auth-Token' => $token]);
    $me =  $r->response['data']['user'];
    
    $this->assertEquals($data['email'],        $me['email']);
    $this->assertEquals($data['name'],         $me['name']);
    $this->assertEquals($user->id,             $me['id']);
    
    //Now with the cookie
    $r     =  $client->put('session', Array('email' => $data['email'], 'password' => $data['password'], 'cookie'=>1));
   
    preg_match('/^([^=]+)=([^;]+)/', $r->headers->set_cookie, $matches);
    
    $cookie_name  = $matches[1];
    $token        = $matches[2];
    
    $this->assertEquals(SESSION_COOKIE, $cookie_name);
    $r   = $client->get('session', [], ['X-Auth-Token' => $token]);
    $me =  $r->response['data']['user'];
    
    $this->assertEquals($data['email'],        $me['email']);
    $this->assertEquals($data['name'],         $me['name']);
    $this->assertEquals($user->id,             $me['id']);
  }
}