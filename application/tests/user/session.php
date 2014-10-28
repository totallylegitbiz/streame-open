<?

class User_Session_Test extends TestCase {

  function test_session() {
    
    $user1  = $this->generate_user();
    $token  = $user1->generate_session();
    
    $user2  = Orm('user')->by_session_token($token);
    $this->assertEquals($user1->id, $user2->id);
  
    $user3  = Orm('user')->by_session_token('eee');
    $this->assertTrue($user3 == null);
    
    $user4  = Orm('user')->by_session_token($token);
    $this->assertEquals($user1->id, $user4->id);
    
  }  
  
  
}