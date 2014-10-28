<?

Class Controller_Static_Images extends Kohana_Controller {
  
  public function action_index () {
  
    try {
    
      $size_code = $this->request->param('size');
      $hash      = $this->request->param('hash');
      $format    = $this->request->param('ext');
      
      $format_array = Array('jpg'=>Image::JPG, 'png'=>Image::PNG, 'gif'=>Image::GIF );
      
      $out_format = $format_array[$format];
      
      if (!$r = Image::parse_code($size_code)) {
        Logger::error("Resize code does not match anything: %s", $size_code);
        header('HTTP/1.1 404 Not Found', TRUE, 404);
        throw new Exception("Invalid resize code: " . $size_code);
      }
      
            
      $out_w           = $r['width'];
      $out_h           = $r['height'];
      $resize_method   = $r['type'];
      
      $orig = Orm('image')->where('hash','=', $hash)->order_by('id', 'desc')->find();
      
      if (!$orig->loaded()) {
        header('HTTP/1.1 404 Not Found', TRUE, 404);
        Logger::error("Image hash not found %o", $hash);
        throw new Exception("Image ID not found: " . $hash); 
      }
      
      
      $orig_image = $orig->original_image();
      
      $mime = $format == 'jpg'?'jpeg':$format;
      
      
      $expires = 60*60*24*365;
      header("Pragma: public");
      header("Cache-Control: maxage=".$expires);
      header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');
      header("Content-Type: image/{$mime}");
      
      if ( $out_h > 0 || $out_w > 0 ) {
        //Let's resize
        $new_image = $orig_image->resize($out_w, $out_h, $resize_method);
  
        if ($new_image->width == $orig_image->width && $new_image->height == $orig_image->height && $out_format == $orig_image->format ) {
          //Looks like we don't need to resize
          $fp = fopen($orig->original_file(), 'rb');
          fpassthru($fp);  
        } else {          
          $new_image->save(null, $out_format, 90);
        }
        
      } else {
        $fp = fopen($orig->original_file(), 'rb');
        fpassthru($fp);
      }
      
      Event::add('image_resize', Array('image_resize'=>$size_code), round((microtime(true) - START_MTS)*1000));
    } catch (Exception $e) {
      header("HTTP/1.1 500 Internal Server Error");
      Logger::error("Exception: %o", $e->getMessage());
      if (IS_DEV) {
        throw $e;
      }
      
      echo 'INTERNAL ERROR';
      
    }
    exit;
  }

}