<?

Class Bitly {

  static function shorten ($url) {
    
    $params = Array(
      'login'   => BITLY_USER, 
      'apiKey'  => BITLY_API_KEY,
      'longUrl' => $url,
      'format'  => 'json'
    );
  
    $api_url = http_build_url('http://api.bitly.com/v3/shorten/', $params);
    
    try {
      $r = Remote::json($api_url);
    } catch (Exception $e) {
      Logger::error("Error creating short url: %s", $e->getMessage());
      return $url; 
    }
    
    return ifset($r['data']['url'], $url);
  
  }

}
