<?

Class Askgeo_Api {

  function query($params) {    
    $url = 'http://api.askgeo.com/v1/446/' . ASKGEO_API_KEY . '/query.json?' . http_build_query($params);
    return Remote::json($url, YEAR);
  }
  
  function get_timezone ($lat, $lng) {
    $r = $this->query(Array('points'=>$lat.','.$lng,'databases'=>'TimeZone'));
    return isset($r['data'][0])?$r['data'][0]['TimeZone']['TimeZoneId']:null;
  }
  
}