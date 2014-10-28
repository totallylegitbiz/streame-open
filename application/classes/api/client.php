<?
  
Class Api_Client extends Rest_Client {

  var $options = Array(
    'base_url'       => BASE_API_ENDPOINT,
    'format'         => 'json',
    'default_params' => Array()
  );
  
  function set_token($token) {
    $this->options['headers']['X-Auth-Token'] = $token;
  }

}