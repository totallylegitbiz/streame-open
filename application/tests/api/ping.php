<?

class Api_Ping_TestCase extends TestCase {

  public function test_ping() {
  
    $client = new Api_Client();
      
    $r = $client->get('ping');
    
    $this->assertEquals('PONG', $r->response['data']);  
    
  }  
  
}