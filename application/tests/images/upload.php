<?

class Test_Images_Upload extends TestCase {

  public function test_upload() {
    
    $file = __DIR__ . '/assets/cat.gif';
    
    $image = Orm('image')->upload($file, true);
    echo $image->src('x');    

  }
  
}