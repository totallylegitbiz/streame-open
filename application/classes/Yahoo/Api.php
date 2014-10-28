<?


Class Yahoo_Api {
  
  static function query($url, $args = [], $extra_flags ='') {
      
    $args["flags"] = "J" . $extra_flags;
       
    $consumer = new OAuthConsumer(YAHOO_CONSUMER_KEY, YAHOO_CONSUMER_SECRET);  
    $request = OAuthRequest::from_consumer_and_token($consumer, NULL,"GET", $url, $args);  
    $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $consumer, NULL);  
    $url = sprintf("%s?%s", $url, OAuthUtil::build_http_query($args));  
    $ch = curl_init();  
    $headers = array($request->to_header());  
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);  
    curl_setopt($ch, CURLOPT_URL, $url);  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);  
    $rsp = curl_exec($ch);  

    return json_decode($rsp, true); 
    
  }
  
  static function placefinder($location) {
    $url = 'http://yboss.yahooapis.com/geo/placefinder';
    
    $q = ['location' => $location];
    
    $r = self::query($url, $q,'TR');
    if (!$r['bossresponse']['placefinder']['count']) {
      throw new Except('Unable to geocode: %o', $location);
    }
    return $r['bossresponse']['placefinder']['results'][0];
  }
   
}