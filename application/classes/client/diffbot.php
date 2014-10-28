<?

Class Client_Diffbot{
  
  static $api_base = 'http://api.diffbot.com/v2';
  
  static function query($path, $params, $ttl = DAY) {
    
    $params['token'] = DIFFBOT_API_TOKEN;
    
    Logger::debug("Calling %o",  self::$api_base . $path . '?' .  http_build_query($params));
    return Remote::json ( self::$api_base . $path . '?' .  http_build_query($params), $ttl);
    
  }
  
  static function product($url, $timeout = 10000) {
    return self::query('/product', ['url'=>$url, 'timeout'=>$timeout]);
  }
  
}