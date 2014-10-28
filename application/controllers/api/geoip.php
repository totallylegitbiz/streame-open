<?

Class Controller_Api_Geoip extends Api_Controller {
  
  function action_get() {
    
    $geo = @geoip_record_by_name ($this->remote_ip());
    
    $this->response->payload = [
      'geoip'=>[
        'ip'    => $this->remote_ip(), 
        'lat'   => $geo['latitude'], 
        'lng'   => $geo['longitude'],
        'city'  => $geo['city'],
        'region'=> $geo['region'],
      ]
    ];
    
  }

}