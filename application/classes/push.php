<?

Class Push {
  
  static function event ( $channels, $event, $obj = Array() ) {
  
    $pusher = new Pusher(PUSHER_API_KEY, PUSHER_API_SECRET, PUSHER_APP_ID);

    if (!is_array($channels)) {
      $channels = Array($channels);
    }
    
    foreach ($channels as $k=>$channel) {
      $channels[$k] = self::get_channel($channel);
    }
    
    Logger::debug("Pushing %o to %o", $event, $channels);
    $obj = Sanitize::arrayitize($obj);
    
    $payload = Array(
      'data'       => $obj,
      'message_id' => sha1(microtime(1) . rand())
    );
    
    foreach ($channels as $channel) {
      $payload['channel'] = $channel;
      $payload['all_channels'] = $channels;
      $pusher->trigger($channel, $event, $payload);  
    }
    
  }
  
  static function get_channel( $name ) {
  
    if (IS_DEV) {
      return str_replace('.', '_', BASE_DOMAIN) .'_' .  $name;
    }
   
    return $name;
     
  }
  
}