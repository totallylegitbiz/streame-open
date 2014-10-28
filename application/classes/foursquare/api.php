<?

// Yeah fill this in
/*
 * $api = new Rest_Client(array(
 *     'base_url' => "http://api.twitter.com/1/", 
 *     'format' => "json"
 * ));
 * $result = $api->get("statuses/public_timeline");
 * if($result->info->http_code < 400)
 *     json_decode($result->response);
*/

// https://developer.foursquare.com/docs/venues/search

Class Foursquare_Api extends Rest_Client {
  var $options = Array(
    'base_url'       => 'https://api.foursquare.com/',
    'format'         => 'json',
    'default_params' => Array(
      'client_id'     => FOURSQUARE_CLIENT_ID, 
      'client_secret' => FOURSQUARE_CLIENT_SECRET,
      'v'=>20120901
    )
  );
  
  function venue_search ($position, $query = '', $limit = 10, $intent = 'browse', $radius=100000 ) {
  
    if (!is_array($position)) {
      $r = Google_Api::latlng($position);
      $position = implode(',', $r);
    }
    
    $query = Array(
      'll'     => $position,
      'query'  => $query, 
      'limit'  => $limit, 
      'intent' => $intent,
      'radius' => $radius
    );
    
    $r = $this->get('/v2/venues/search', $query);
    
    Logger::debug("Foursquare Api - Venue Search - %o: %o", $query, $r->response);

    return Sanitize::encode_html($r->response['response']['venues']);
    
  }
  
  // $r =  Foursquare_Api::factory()->venue_search('Brooklyn, NY', 'rebar');
  
  function venue ( $venue_id ) {
    $r = $this->get('/v2/venues/' . $venue_id);
    
    return Sanitize::encode_html($r->response['response']['venue']);
  }
}