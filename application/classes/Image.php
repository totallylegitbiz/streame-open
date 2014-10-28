<?


Class Image {

  const MAX_FILE_SIZE = 10000000;

  const E_UNKNOWN_TYPE = 1;
  const E_TOO_LARGE = 2;

  const GIF  = 1;
  const JPG  = 2;
  const PNG  = 3;

  const T_BOUNDING_BOX     = 1;
  const T_BOUNDING_BOX_MAX = 2;
  const T_CROP             = 3;
  const T_WIDTH_PRIORITY   = 4;
  const T_STRETCH          = 5;

  var $im = null;

  var $file, $width, $height, $format = null;
  

  function __construct( $file = null ) {

    if (strlen($file)) {
      $this->load($file);
    }

  }

  static function parse_code($code) {
  
    if (!preg_match('/^([0-9]+)?x([0-9]+)?(c|b|bm|w)?$/',$code, $matches)) {
      return;
    }
  
    $out_w  = ifset($matches[1],0);
    $out_h  = ifset($matches[2],0);
    $type   = ifset($matches[3],'c');
     
    switch ($type) {
      case 'b':
        $resize_method = Image::T_BOUNDING_BOX;
        break;
      case 'bm':
        $resize_method = Image::T_BOUNDING_BOX_MAX;
        break;
      case 'c':
        $resize_method = Image::T_CROP;
        break;
      case 'w':
        $resize_method = Image::T_WIDTH_PRIORITY;
        break;
  
      default:
        throw new Exception('Resize method not found:' . $type);
    }
    
    return Array('width'=>$out_w, 'height'=>$out_h, 'type'=>$resize_method);
  
  
  }

  static function typeToExt ( $type ) {
    
     switch ($type)
      {
        case Image::GIF :return 'gif';
        case Image::JPG :return 'jpg';
        case Image::PNG :return 'png';
        default:
          throw new Except_Public ('Only GIF, JPG, PNG allowed', Image::E_UNKNOWN_TYPE );
      }
          
  }
    
  
  function load ( $file ) {

    if (!file_exists($file)) {
      throw new Exception('Files does not exist: ' . $file);
    }

    if (filesize($file) > Image::MAX_FILE_SIZE ) {
      throw new Except_Public('File size too large', Image::E_TOO_LARGE);
    }

    list($width, $height, $type, $attr) = @getimagesize($file);

    if (empty($type)) {
      throw new Except_Public('Unknown image type', Image::E_UNKNOWN_TYPE);
    }
  
    if (!$width || !$height) {
      throw new Except_Public('Unknown image size', Image::E_UNKNOWN_TYPE);  
    }    
    $this->width  = $width;
    $this->height = $height;
    $this->format = $type;

    $this->open($file, $type);
    
    return $this;

  }

  static function is_animated($filename) {
    
    if(!($fh = @fopen($filename, 'rb')))
        return false;
        
    $count = 0;
    //an animated gif contains multiple "frames", with each frame having a
    //header made up of:
    // * a static 4-byte sequence (\x00\x21\xF9\x04)
    // * 4 variable bytes
    // * a static 2-byte sequence (\x00\x2C)

    // We read through the file til we reach the end of the file, or we've found
    // at least 2 frame headers
    while(!feof($fh) && $count < 2)
        $chunk = fread($fh, 1024 * 100); //read 100kb at a time
        $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00\x2C#s', $chunk, $matches);

    fclose($fh);
    return $count > 1;
    
  }

  function rotate($degrees) {
    
    $this->im = imagerotate ( $this->im, $degrees, 0 );
    
    //Yeah, this is hacky, but I'm not feeling it.
    
    if ($degrees != 0 && $degrees != 180 && $degrees != -180) {
      $tmp_h = $this->height;
      $tmp_w = $this->width;
      
      $this->height = $tmp_h;
      $this->width  = $tmp_w;
    }
    
    return $this;
    
  }
  
  function puzzle_cvec() {
    return puzzle_fill_cvec_from_file($this->file);
  }
  
  function exifRotate ( $orientation ) {
    switch($orientation)
    {
      case 1: // nothing
      break;

      case 2: // horizontal flip
      #$image->flipImage($public,1);
      break;

      case 3: // 180 rotate left
       $this->rotate(180);
      break;

      case 4: // vertical flip
      #$this->flipImage($public,2);
      break;

      case 5: // vertical flip + 90 rotate right
      #$this->flipImage($public, 2);
      
       $this->rotate(-90);
      break;

      case 6: // 90 rotate right
       $this->rotate(-90);
      break;

      case 7: // horizontal flip + 90 rotate right
      
       $this->rotate(-90);
      break;

      case 8:    // 90 rotate left
       $this->rotate(90);
      break;
    }
    
    return $this;
  }
  
  function open ( $file, $type ) {

    switch ($type)
    {
      case Image::GIF : $im = imagecreatefromgif($file);  break;
      case Image::JPG : $im = imagecreatefromjpeg($file); break;
      case Image::PNG : $im = imagecreatefrompng($file);  break;
      default:
        throw new Except_Public ('Only GIF, JPG, PNG allowed', Image::E_UNKNOWN_TYPE );
    }

    if (!$im) {
      throw new Exception('Unable to open:' . $file);
    }

    $this->im = $im;
    $this->file = $file;
    
    $meta = @exif_read_data($file);
    
    if (isset($meta['Orientation']) && is_numeric($meta['Orientation'])) {
      $this->exifRotate($meta['Orientation']);
    }
    
    return $im;

  }

  function crop ( $x1, $y1, $x2, $y2 ) {

    if (!$this->im) {
      throw new Exception('No image loaded');
    }

    $width  = $x2 - $x1;
    $height = $y2 - $y1;

    $im = imagecreatetruecolor($width, $height);

    Logger::debug("imagecopy(%o,%o,%o,%o,%o,%o)",0, 0, $x1, $y1, $width, $height);
    imagecopy($im, $this->im, 0, 0, $x1, $y1, $width, $height);

    $out          = new Image();
    $out->im      = $im;
    $out->width   = $width;
    $out->height  = $height;
    return $out;

  }

  static function calc_resize_by_code($code, $in_width, $in_height) {
    $r = self::parse_code($code);
    return self::calc_resize($in_width, $in_height, $r['width'], $r['height'], $r['type']);
  }
  
  static function calc_resize($in_width, $in_height, $width, $height, $type) {
  
    $src_w    = $input_w = $in_width;
    $src_h    = $input_h = $in_height;
    $src_x    = $src_y = 0;
   
    $in_ratio = $input_w / $input_h;
  
    switch ($type) {
    
      case Image::T_CROP:
        
        if ($width == 0 && $height == 0) {
          $width  = $in_width;
          $height = $in_height;
        } else if ($width == 0 ) {
          $width = $height * $in_ratio;
        } else if ($height == 0 ) {
          $height = $width / $in_ratio;
        } else {
    
          $resize_ratio = $width / $height;
    
          //TODO: Resize for images wider than high isn't working too well
          if ($in_ratio >= $resize_ratio) {
            $src_w = floor($input_h * $resize_ratio);
            $src_x = floor(abs($input_w - $src_w) / 2);
          } else {
            $src_h = floor($input_w / $resize_ratio);
            $src_y = floor(abs($input_h - $src_h) / 2); //Focus on the top of the image
          }
    
        }
      break;
     
      case Image::T_WIDTH_PRIORITY: 
      
        $out_height   = $width / $in_ratio;
        $resize_ratio = $width / $height;
        
        if ($out_height > $height) {
          
          if ($in_ratio >= $resize_ratio) {
            $src_w = floor($input_h * $resize_ratio);
            $src_x = floor(abs($input_w - $src_w) / 2);
          } else {
            $src_h = floor($input_w / $resize_ratio);
            $src_y = floor(abs($input_h - $src_h) / 2 * .4); //Focus on the top of the image
          }
          
        } else {
          $height = $out_height;  
        }
       
      
      break;
      
      case Image::T_BOUNDING_BOX_MAX:
        /**
         * In this case, we only resize if the image is larger than the bounding box.
         */
      
        $dst_w = $input_w;
        $dst_h = $input_h;
        
        if (!$height) {
          $height = ($width / $input_w ) * $input_h;
        }
        
        if (!$width) {
          $width = ($height / $input_h) * $input_w;
        }
        
        if ($width && $dst_w > $width) {
          $dst_h  = ($width / $input_w ) * $input_h;
          $dst_w  = $width;
        }
        
        if ($height && $dst_h > $height) {
          $dst_w  = ($height / $input_h ) * $input_w;
          $dst_h  = $height;
        }
        
        $src_x  = 0;
        $src_y  = 0;
        $width  = floor($dst_w);
        $height = floor($dst_h);
        $src_w  = $input_w;
        $src_h  = $input_h;
            
      break;
      case Image::T_BOUNDING_BOX:
        
        /**
         * Resize the image to fit within a bounding box
         */

        $dst_w = $input_w;
        $dst_h = $input_h;
        
        if (!$height) {
          $height = ($width / $input_w ) * $input_h;
        }
        
        if (!$width) {
          $width = ($height / $input_h) * $input_w;
        }
        
        $dst_h  = ($width / $input_w ) * $input_h;
        $dst_w  = $width;
        
        if ($dst_h > $height) {
          $dst_w  = ($height / $input_h ) * $input_w;
          $dst_h  = $height;
        }
        
        $src_x  = 0;
        $src_y  = 0;
        $width  = floor($dst_w);
        $height = floor($dst_h);
        $src_w  = $input_w;
        $src_h  = $input_h;
        
      break;
      
      case Image::T_STRETCH:
      break;
    }

    
    return Array(
      'width'  => round($width), 
      'height' => round($height), 
      'src_x'  => round($src_x),
      'src_y'  => round($src_y),
      'src_w'  => round($src_w),
      'src_h'  => round($src_h)
      );
  
  }
  
  
  
  function resize ( $width, $height, $type = Image::T_CROP ) {
  
    $r = $this->calc_resize($this->width, $this->height, $width, $height, $type);
    
    $src_x  = $r['src_x'];
    $src_y  = $r['src_y'];
    $width  = $r['width'];
    $height = $r['height'];
    $src_w  = $r['src_w'];
    $src_h  = $r['src_h'];


    $im = imagecreatetruecolor($width, $height);

    imagecopyresampled($im, $this->im, 0, 0, $src_x, $src_y, $width, $height, $src_w, $src_h);
    
    imagesavealpha($im,true);
    imagealphablending($im, false);
        
    $out = new Image();
    $out->im      = $im;
    $out->width   = $width;
    $out->height  = $height;

    return $out;

  }

  
  function save ( $file, $type = Image::JPG, $quality = 85) {

    if (!is_null($file) && !file_exists(dirname($file)) && !mkdir(dirname($file), 0777, true)) {
      throw new Exception('Error creating directory:' . dirname($file));
    }

    if (!is_null($file) && !is_writable(dirname($file))) {
      throw new Exception('Unable to write to:' . $file);
    }

    imagesavealpha($this->im,true);
    imagealphablending($this->im, false);
    
    switch ($type)
    {
      case Image::GIF : imagegif ($this->im, $file);      break;
      case Image::JPG : /* imageinterlace($this->im, true); */ imagejpeg($this->im, $file, $quality);  break;
      case Image::PNG : imagepng ($this->im, $file,  0);  break;
      default:
        throw new Except_Public ('Only GIF, JPG, PNG allowed', Image::E_UNKNOWN_TYPE );

    }

    return $this;
  }

}

