<?

class General_Lock_TestCase extends TestCase {

  public function test_lock() {
    $key   = sha1(rand());
    $lock1 = new Lock($key);
    $lock2 = new Lock($key);
    
    $this->assertTrue($lock1->lock());
    $this->assertFalse($lock2->lock());
    
    $this->assertEquals(1,$lock1->unlock());
    $this->assertEquals(null, $lock2->unlock());
    
  }
  
  public function test_lock_expire() {
    $key   = sha1(rand());
    $ttl   = 2;
    
    $lock1 = new Lock($key, $ttl);
    $lock2 = new Lock($key, $ttl);
    
    $this->assertTrue($lock1->lock());
    $this->assertFalse($lock2->lock());
    
    sleep($ttl + 1); //Let's give is a second to expire.
    
    $this->assertTrue($lock2->lock());
    $this->assertFalse($lock2->lock());
    
  }
  
}