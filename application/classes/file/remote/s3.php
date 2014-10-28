<?

Class File_Remote_S3 {
  
  var $s3     = null;
  var $bucket = null;
  var $base_path = AWS_S3_BUCKET_BASE;
  
  public function __construct() {
    $this->s3        = new Amazon_S3(AWS_ACCESS_ID, AWS_ACCESS_KEY);  
    $this->bucket    = AWS_S3_BUCKET;
    $this->base_path = AWS_S3_BUCKET_BASE;
  }
  
  public function write($filename_remote, $filename_local, $tries = 3) {
  
    try {
      $size = filesize($filename_local);
      $fp   = fopen($filename_local, 'rb');
      $r = $this->s3->putObject(
        $this->s3->inputResource($fp, $size), 
        $this->bucket, 
        $this->base_path . '/' . $filename_remote, 
        Amazon_S3::ACL_PUBLIC_READ
      );
      
    } catch (Exception $e) {
      Logger::error("Exception when uploading %o to %o %o: %o",$tries, $filename_local, $filename_remote, $e);
      if ($tries) {
        usleep(250000); //Wait 1/4 second
        Logger::error("Trying again to upload to: %o", $filename_remote);
        $this->write($filename_remote, $filename_local, $tries - 1);
      }
    }
  } 
  
  public function url($filename) {
    return '//s3.amazonaws.com/' . $this->bucket . '/' . $this->base_path . '/' . $filename;
  }
  
  public function read($filename) {
  
    $tmp_file_name = tempnam('/tmp', $this->bucket);
    
    #//This may look smart, but it's MUCH slower
    #$remote_url = 'https://s3.amazonaws.com/' . $this->bucket . '/' . $this->base_path . '/' . $filename;
    #copy($remote_url, $tmp_file_name);
    
    $fp = fopen($tmp_file_name, 'wb');
    $this->s3->getObject($this->bucket, $this->base_path . '/' . $filename, $fp);
    
    register_shutdown_function(function() use ($tmp_file_name) {
      @unlink($tmp_file_name);
    });
    
    return $tmp_file_name;
  
  }
  
  public function delete($filename) {
    return $this->s3->deleteObject($this->bucket, $this->base_path . '/' . $filename);
  }
   
}
