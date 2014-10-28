<?

Class Remote {

  static $cache_enabled = true;
  
  static function url_params ( $url, $cache_time = null) {

    $params = self::request($url, $cache_time);
    
    @parse_str($params, $array);
    
    return $array;
 
  }
  
  static function request ( $url, $cache_time = null, $cache_key = null ) {

    if (!is_array($cache_key)) {
      $cache_key = Array($cache_key);
    }
    
    $cache_key[] = $url;
    $cache_key[] = $cache_time;
    
    $cache_key   = implode('::', $cache_key);
    
    //We're going to check the cache
    if (self::$cache_enabled && $cache_time !== null) {
      
      $cache  = Cache_Remote::instance();
      
      $result = $cache->get($cache_key);
      
      if ($result) {
        //Logger::debug("Url: Read from cache: %s", $url);
        return $result;
      }
      
    }
      
    //No cache hit, let's do the request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    
    if (!$result = curl_exec($ch)) {
      return $result;
    }
    
    //If cacheable, let's cache.
    if (self::$cache_enabled && $cache_time !== null && $result !== null) {
      //Logger::debug("Url: Write to cache: %s", $url);
      $cache->set($cache_key, $result, $cache_time);
    }
    
    return $result;
    
  }
  
  static function json_post ( $url, $params, $cache_time = null ) {
  
    if ($cache_time) {
      $key = md5(serialize(Array('json_post', $url, $params,$cache_time)));
      if ($result = Cache_Remote::instance()->get($key)) {
        return $result;
      }
    }
    
    if (is_array($params)) {
      $params = http_build_query($params);
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST      ,1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
 
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($ch);
    
    //Logger::debug("Url: Requesting: %s - Result %s", $url, $result);

    $result = @json_decode($result, true);
  
    if (!curl_errno($ch) && $cache_time) {
      Cache_Remote::instance()->set($key, $result, $cache_time);
    }
      
    return $result;
  }
  
  
  static function jsonp ( $url, $cache_time = null, $assoc = true) {
    return self::json ( $url, $cache_time, $assoc, true );
  }
 
  static function json ( $url, $cache_time = null, $assoc = true, $jsop = false ) {
    
    //Logger::debug("Url: Requesting: %s (Cache: %d)", $url,$cache_time);
    
    $key = 'URL_CACHE::' . $assoc . '::' . $cache_time .'::' . $url;
    
    //We're going to check the cache
    if (self::$cache_enabled && $cache_time !== null) {
      
      $cache  = Cache_Remote::instance();
      
      $result = $cache->get($key);
      
      if ($result) {
        
        //Logger::debug("Url: Read from cache: ", $url);
        return $result;
      }
      
    }
      
    $contents = file_get_contents($url);
  
    if ($jsop) {
      if (preg_match('/^([a-z0-9_]+)?\((.*)\)/', $contents, $matches)) {
        $contents = $matches[2];   
      } 
    } 
    
    $result = @json_decode($contents,$assoc);
    
    if (self::$cache_enabled && $cache_time !== null && $result !== null) {
      //Logger::debug("Url: Write to cache: ", $url);
      $cache->set($key, $result, $cache_time);
    }
    
    return $result;
    
    
  }
  
}
