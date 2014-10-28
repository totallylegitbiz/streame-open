<?


Class Google_Api {
  
  static function geocode ( $address ) {
  
    $params = ['address'=>$address,'sensor'=>'false','key' => GOOGLE_API_SERVER_KEY];    
    
    $url    = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_str($params);
    $r      = Remote::json($url, MONTH, true);
    
    if (!isset($r['status']) || $r['status'] != 'OK') {
      throw new Except('GOOGLE API ERROR: %o - %o', ifset($r['status']), $url);
    }
    
  }
  
  static function latlng ( $address ) {
    $geocode          = self::geocode($address);
    Logger::info("%o", $geocode);
    
    if (!isset($geocode['results'][0])) {
      return [null,null];
    }
    
    $address  = $geocode['results'][0];  
    $geometry = $address['geometry']['location'];
  
    return Array(ifSet($geometry['lat']), ifSet($geometry['lng']));
    
  }  

  static function getStateByZip($zip) {
    try {
      $r = self::getAddressComponents(self::geocode($zip),Array('administrative_area_level_1'));
    } catch (Exception $e) {
      return null;
    }
    return ifSet($r['administrative_area_level_1']['short_name']);
  }

 static function getAddressComponents($geocode, $types = array('country', 'subpremise','route','sublocality','locality', 'postal_code','administrative_area_level_1')){

		if(isset($geocode['status']) && strtoupper($geocode['status']) == 'OK'){
			if(isset($geocode['results'][0]['address_components'])){
				$addressComponents = $geocode['results'][0]['address_components'];
				if(!is_array($types)){
					$types = array($types);
				}
				$tmp = array();
				foreach($addressComponents as $a){
					foreach($a['types'] as $t){
						if(in_array($t, $types)){
							$tmp[$t] = $a;
							unset($tmp[$t]['types']);
						}
					}
				}
				if(!empty($tmp)) return $tmp;
				else return false;
			}
			else return false;
		}
		else{
			trigger_error('Google Geocoding API - Status: '.$geocode['status']);	
			return false;
		}
	}
	
	static function timezone($lat, $lng) {
	
    $params = Array('location'=>$lat . ',' . $lng,'sensor'=>'false', 'timestamp' => time()/* ,'key' => GOOGLE_API_SERVER_KEY */);    
    $url    = 'https://maps.googleapis.com/maps/api/timezone/json?' . http_build_str($params);
  
    return Remote::json($url, MONTH, true);
        
	}


  static function places_textsearch ( $query, $types = null) {
    $query = urlencode($query);
	  $url = "https://maps.googleapis.com/maps/api/place/textsearch/json?query={$query}&sensor=false&types={$types}&key=" . GOOGLE_API_SERVER_KEY;
	  return Remote::json($url, MONTH, true);
	}
	
	
  static function places_near ( $lat, $lng, $radius, $types, $name, $pagetoken = '') {
    $name = urlencode($name);
	  $url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location={$lat},{$lng}&radius={$radius}&pagetoken={$pagetoken}&types={$types}&name=${name}&sensor=false&key=" . GOOGLE_API_SERVER_KEY;
	  return Remote::json($url, MONTH, true);
	}
	
	static function place_detail ($ref_id) {
  	$url = "https://maps.googleapis.com/maps/api/place/details/json?reference=" . urlencode($ref_id) . '&sensor=false&key=' . GOOGLE_API_SERVER_KEY;
  	return Remote::json($url, MONTH, true);
	}
	
	
	
}