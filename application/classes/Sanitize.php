<?

Class Sanitize {

  static function ensure_html_encoded_once ($text) {
    return self::encode_html(self::ensure_html_decoded($text));
  }
  
  static function ensure_html_decoded ($text) {
    
    if (self::decode_html($text) != $text) {
      return self::ensure_html_decoded(self::decode_html($text));    
    }
    
    return $text;
    
  }
  
  static function encode_html($text){
    
    if (is_array($text)) {
      return self::encode_html_array($text);
    }
    
    return htmlentities($text, ENT_QUOTES, 'UTF-8');
    
  }
  
  //Fixes up a url
  static function decode_html($text){
    return html_entity_decode($text, ENT_QUOTES, 'UTF-8');
  }
  
  static function encode_html_array ( $array ) {
  
    $out = Array();
  
    foreach ($array as $key=>$value) {
  
      if (is_array($value)) {
        $out[$key] = self::encode_html_array($value);
      } else {
        $out[$key] = self::encode_html($value);
      }
  
    }
  
    return $out;
  
  }
     
  static function keyify ($obj) {
    return sha1(serialize($obj));
  }
  
  static function arrayitize ( $obj, $date_as_ts = true ) {
    if (!is_object($obj) && !is_array($obj)) {
      return $obj;
    }
  
    if ($obj instanceof ArrayObject) {
      $out = Array();
      foreach ($obj as $k=>$child) {
        $out[$k] = self::arrayitize($child,$date_as_ts);
      }
      return $out;
    }
    
    if ($obj instanceof Traversable) {
      $out = Array();
      foreach ($obj as $k=>$child) {
        $out[] = self::arrayitize($child,$date_as_ts);
      }
      return $out;
    }
    
    if ($obj instanceof Daytime) {
      if ($date_as_ts) { 
        return (integer) ((string) $obj);
      } else {
        return $obj->format('c');
      }
    }
    
    if ($obj instanceof MongoDate) {
      return $obj->sec;
    }
    if ($obj instanceof MongoId) {
      return $obj->{'$id'};
    }
    
    if (is_object($obj)) {
      if (method_exists($obj, 'to_array')) {
        $obj_array = $obj->to_array();
      } else {
        if (IS_DEV) { print_r($obj);throw new Exception('Object cannot be rendered as an array, make sure to set to_array()'); }
        $obj_array = Array();
      }
    } else {
      $obj_array = $obj;
    }
  
    foreach ($obj_array as $k=>$v) {
      if ($v instanceof Orm && !$v->loaded()) {
        $obj_array[$k] = Array();
      } else {
        $obj_array[$k] = self::arrayitize($v,$date_as_ts);
      }
    }
  
    return $obj_array;
  
  }
  
  static function strip_after($text, $needle) {
    $pos = strpos($text, $needle);
    if ($pos === false) {
      return $text;
    }
    return substr($text, 0,$pos);  
  }
  static function tagify ( $text, $spacer = '-' ) {
  
    //Handle if array
    if (is_array($text)) {
      foreach ($text as $key=>$value) {
        $text[$key] = self::tagify($value, $spacer);
      }
      return $text;
    }
  
    $text = self::decode_html($text);
    $text = self::normalize($text);
  
    $text = str_replace('&', 'and', $text);
    return  str_replace(' ', $spacer, trim(preg_replace('/([^a-z0-9]+)/', ' ', strtolower($text))));
  }
  
  static function normalize ( $text ) {

    $map = Array(
      'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A',
      'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',
      'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U',
      'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a',
      'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i',
      'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u',
      'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f'
    );
    
    return strtr($text, $map);
  }

  function array_flatten(Array $array) {
    
    $out = Array();
    
    foreach ($array as $k=>$v) {
      
      if (is_array($v)) {
        foreach (flatten_array($v) as $append_k=>$append_v) {
          $out[$k.'.'.$append_k] = $append_v;
        }
        continue;
      }
      
      $out[$k] = $v;
      
    }
    
    return $out;
    
  }
  
  static function remove_utm($url) {
    $url =  preg_replace('/&?utm_(.*?)\=[^&]+/','',$url);
    return preg_replace('/\?$/','',$url); //Kill possibly tailing ?
  }
  
  static function redirect_url($url, $follow_limit = 5) {
    
    Logger::debug("Resolving url, limit = %o, %o ", $follow_limit, $url);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, $follow_limit);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)"); 
    
    $a = curl_exec($ch);
    $r = curl_getinfo($ch);
    
    $new_url = $r['url'];
    
    if ($r['http_code'] == 301 && preg_match('/Location: (.*)/', $a, $r)) {
      $new_url = trim($r[1]);
    }

    //If it changed, let's dig deeper
    if (($new_url !== $url) && $follow_limit) {
      return self::redirect_url($new_url, $follow_limit - 1);
    }
    
/*
    $new_url = preg_replace('/^HTTP/',  'http', $new_url);
    $new_url = preg_replace('/^HTTPS/', 'https', $new_url);
*/
    
    if (!parse_url($new_url, PHP_URL_PATH)) {
      $new_url .='/';
    } 
    
    return $new_url;
    
  }

  static function has_subdomain($url) {
    return !preg_match('/^(http[s]?:\/\/)?(www\.)?([^\.]+)\.[a-z]{2,4}(\.[a-z]{2,4})?(\/.*)?$/', $url);
  }
  
  static function purify_html ($dirty_html) {
    
    $config = HTMLPurifier_Config::createDefault();
    //MaxImgLength
    
    $config->set('HTML.MaxImgLength',    null);
    $config->set('Cache.SerializerPath', '/tmp');
    
    /*
    $config->set('HTML', 'MaxImgLength', '700');
    $config->set('CSS', 'MaxImgLength', '700px');
    */
    
    $purifier = new HTMLPurifier($config);
    return $purifier->purify($dirty_html);

    
  }
  
  static function is_url($url) {
    //Strip everything after the #
    $url = preg_replace('/\#.*$/', '', $url);
    return filter_var($url, FILTER_VALIDATE_URL);
  }
  static function is_assoc($array) {
    return (bool)count(array_filter(array_keys($array), 'is_string'));
  }

  static function url_domain($url) {
  
    if (preg_match('/^http[s]?:\/\/(www\.)?([^\/]+)(.+)/', $url, $matches)) {
      return $matches[2];      
    }
    
    return null;
  }
  
  static function filter($field, $value, $default = null, $filter = null, $options = null) {
   
    if ($value === null && $default !== null) {
      return $default;
    }
    
    if ($value === null && $default === null && ifSet($options['allow_null'])) {
      return null;  
    }
        
    
    if ($filter == FILTER_VALIDATE_CLOSURE) {
      return $options($field, $value);
    }
    
    if ($filter == FILTER_VALIDATE_STR) {
      $value = trim($value);
      
      if (!strlen($value)) {
        return null;
      }  
      return (string) $value;
    }
    
    if ($filter == FILTER_VALIDATE_STR_NOT_EMPTY) {
      $value = trim($value);
      if (!strlen($value)) {
        throw new Except_Filter('Cannot be blank %o: %o', $field, $value);
      }
      return $value;
    }
    
    if ($filter == FILTER_VALIDATE_STR_SET) {
      //This is a string that is a set of strings.
      $ints = Array();
      
      if (!strlen($value)) {
        return $default;
      }
      
      if (!preg_match('/^[A-Za-z\-\_0-9,]+$/', $value)) {
        throw new Except_Filter('Invalid value for %o: %o', $field, $value);
      }
      
      foreach (explode(',', $value) as $str) {
        $str = trim($str);

        if (!count($str)) {
          throw new Except_Filter('Invalid value for %o: %o', $field, $str);
        }

        $strs[] = (string) $str;
      }
      
      return $strs;
    }


    if ($filter == FILTER_VALIDATE_INT_SET) {
      //This is a string that is a set of integers.
      $ints = Array();
      
      if (!preg_match('/^[0-9,]+$/', $value)) {
        throw new Except_Filter('Invalid value for %o: %o', $field, $value);
      }
      foreach (explode(',', $value) as $int) {
        if (!filter_var($int, FILTER_VALIDATE_INT, $options)) {
          throw new Except_Filter('Invalid value for %o: %o', $field, $int);
        }
        $ints[] = (integer) $int;
      }
      
      return $ints;
    }
    
    if ($default !== null && (string)$value == (string)$default) {
      return $default;
    }
    
    if ($filter == FILTER_VALIDATE_DAYTIME) {
    
      if ($value instanceof DateTime) {
        return $value;  
      }
      
      if (is_numeric($value)) {
        return (new Daytime())->setTimestamp($value);
      }
      
      if (strtotime($value) === FALSE) {
        throw new Except_Filter("Value for %o must be a valid date: %o", $field, $value);
      }
      
      return new Daytime($value);
      
    }
    
    if ($filter == FILTER_VALIDATE_IN_SET) {
      if (!in_array($value, $options)) {
        throw new Except_Filter("Value for %o must be one of the following: %o", $field, implode(',', $$options));
      }
      return $value;
    }    
    
    if ($filter == FILTER_VALIDATE_MODEL_ID) {

      $model = $options['model'];
      if (empty($value) || !Orm($model, $value)->loaded()) {
        throw new Except_Filter('Please select a valid %o.', $model);
      }
      return $value;
    }  
    
    if ($filter == FILTER_VALIDATE_PHONE) {

      $value = trim($value);
      
      if (empty($value) || !preg_match('/^\(?([0-9]{3})\)?([ .-]?)([0-9]{3})([.-]?)([0-9]{4})$/', $value)) {
        throw new Except_Filter('Value for %o must be a valid phone number', $field, $value);
      }

      return preg_replace('/([^0-9])/', '',$value);
      
    }    
  
    if ($filter == FILTER_VALIDATE_BOOLEAN) {
      return filter_var($value, $filter, $options);
    }
    
    if ($filter) {
      if (filter_var($value, $filter, $options) === false) {
        throw new Except_Filter("Invalid value for %o: %o", $field, $value);
      }
    }
    
    return $value;
    
  }

}

