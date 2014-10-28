<?

/*
DROP TABLE images;
CREATE TABLE `images` (
  `id`       int(11) unsigned NOT NULL AUTO_INCREMENT,
  `format`   enum('JPG','GIF','PNG') NOT NULL,
  `height`   int(10) unsigned NOT NULL,
  `width`    int(10) unsigned NOT NULL,
  `meta`     text,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hash` varchar(50) DEFAULT NULL,
  `bytes` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hash` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
*/

class Model_Image extends Orm {

  var $_serialize_columns = ['meta'];
  /*

  function on_insert($changed) {
    Event::add('image_upload', Array( 'format' => $this->format), $this->bytes);
  }
*/
  
  function file_path() {
    return 'images/' . $this->hash . '.' . strtolower($this->format);
  }
  
  function original_file() {
    
    $file_remote = new File_Remote();
    $tmp_file = $file_remote->read($this->file_path());
    return $tmp_file;
    
  }
    
  function original_image() {
    return new Image( $this->original_file() );
  }
  
  function src( $resize_code = 'x', $format = 'jpg', $title = 'image') {
  
    if (empty($format)) {
      $format = 'jpg';
    }
    
    $title = Sanitize::tagify($title);
    
    if (!strlen($title)) {
      $title = 'image';
    }
    
    return '//' . STATIC_DOMAIN . "/images/${resize_code}/{$this->hash}/${title}.{$format}";
    
  }

  function upload_remote ( $url ) {
  
    $tmp_file = tempnam('/tmp','image');
    
/*
    if (strpos($url,'//') === 0) {
      $url = 'http:' . $url;
    }
*/
    Logger::debug("Uploading: %o", $url);
    
    if (strpos($url,'%5C/%5C/') === 0) {
      $url = str_replace('%5C/%5C/','http://', $url);
    }
  
    if (!@copy($url, $tmp_file)) {
      throw new Exception("Unable to copy image: " . $url);
    }
    
    $r = $this->upload($tmp_file);
    @unlink($tmp_file);
    
    return $r;
    
  }
  
  function upload ( $file, $force = false ) {

    if (!file_exists($file)) {
      throw new Except_Public('File not found:' . $file);
    }
    
    $sha1           = sha1_file($file);
    
    if (!$force) {
      //Let's find to see if this file already exists;
      $image = Orm('image')->where('hash','=',$sha1)->find();
      
      if ($image->loaded()) {
        //Okay, we already have it, but what if, let's say, it got corrupt, let's see if it still exists
        try {
          $original_file = $image->original_file();
        } catch (Exception $e) {
          Logger::error('Error loading pre-existing image, writing new: %o', $e);
          //So, it errored out, let's copy it again
          $file_remote = new File_Remote();
          $file_remote->write($image->file_path(), $file);
        }
        
        //Logger::info("Image already exists, returning original: %o", $image->id);
        return $image;
      }
    }
    
    $orig_image     = new Image($file);
    $image          = new Model_Image();
    
    $image->height  = $orig_image->height;
    $image->width   = $orig_image->width;
    $image->hash    = $sha1;
    
    
    //Try importing: http://farm9.staticflickr.com/8355/8269070276_28ef56a278_o.jpg
    $image->meta    = @exif_read_data($file);
    $image->bytes   = @filesize($file);
    
    $image->format  = strtoupper(Image::typeToExt($orig_image->format));
    
    $image->is_animated = false;
    
    if ($image->format == 'GIF') {
      $image->is_animated = (boolean) Image::is_animated($file);
    } 
    
    $image->save();

    //Set up where we're saving it.    
    try {
      $file_remote = new File_Remote();
      $r = $file_remote->write($image->file_path(), $file);
    } catch (Exception $e) {
      Logger::error("Error uploading file: %o", $e);
      $image->delete(); 
      throw $e;
    }
    
    Logger::debug("Created: #%o http:%o", $image->id, $image->src('x'));
    
    return $image;
  
  }
  
  function to_array($extra_fields = Array()) {
    return Array(
            'id'     => (integer) $this->id,
            'width'  => (integer) $this->width,
            'height' => (integer) $this->height,
            'format' => $this->format,
            'hash'   => $this->hash,
            'is_animated'   => (boolean) $this->is_animated,
            'domain' => STATIC_DOMAIN,
            'src'    => $this->src()
    );
    
  }
  
}
