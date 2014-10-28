<?

Class Client_Yahoo {
  
  static function yql($query) {
    
    $params = Array(
      'q'      => $query,
      'format' => 'json',
      'ck'     => YAHOO_CONSUMER_KEY,
      'cs'     => YAHOO_CONSUMER_SECRET  
    );
    
    return Remote::json_post ( 'http://query.yahooapis.com/v1/public/yql', $params, 14 * DAY );
    
  }
  
  static function content_analysis($text) {
    
    if (empty($text)) {
      return;
    }
    
    $query = 'select * from contentanalysis.analyze where text="' . str_replace('"', '\"', $text) . '"';
    $r = self::yql($query);

    if (!isset($r['query']['results']['entities'])) {
      return;
    }
    
    return $r['query']['results']['entities']['entity'];
    
  }
  
}