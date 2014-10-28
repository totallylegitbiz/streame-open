<?

class General_Config_TestCase extends TestCase {

  public function test_set() {

    $key = sha1(rand());
    $value = 666;
    
    $this->assertNull(App_Config::get($key));
    App_Config::set($key, $value);
    $this->assertEquals($value, App_Config::get($key));
    App_Config::delete($key);
    $this->assertNull(App_Config::get($key));
    
  } 
}