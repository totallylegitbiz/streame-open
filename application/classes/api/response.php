<?

Class Api_Response {
  
  var $type          = 'JSON';
  var $message       = null;
  var $code          = 200;
  var $status        = 'OK';
  var $payload       = null;
  var $json_callback = null;
  
  function __construct( $payload = null ) {
    $this->payload = $payload;
  }
  
  function set( $payload ) {
    $this->payload = $payload;
  }
  
  function payload_array() {
    //if ((is_object($this->payload) && method_exists($this->payload, 'to_array')) || is_array($this->payload)) {
      return Sanitize::arrayitize($this->payload);
    //} else {
    //  die('s');
    //  return $this->payload;
    //}
  }
  
  function render() {

     switch ($this->code) {
      case 404:
        header('HTTP/1.1 404 Not Found', true, 404);
        break;
      case 401:
        header('HTTP/1.1 401 Unauthorized', true, 401);
        break;
      case 400:
        header('HTTP/1.1 400 Client Error', true, 400);
        break;
      case 500:
        header('HTTP/1.1 500 Internal Server Error', true, 500);
        break;
    }
    
    switch ($this->type) {
      case 'JSONP':
        $output = $this->outputJSONP();
      break;
      case 'JSON':
        $output = $this->outputJSON();
      break;
      default:
        die("Unknown return type");
    }
    
    echo $output; 
  }
  
  public function resultArray() {
  
    $out = Array(
      "code"    => $this->code,
      "message" => $this->message,
      "status"  => $this->status,
      "ts"      => time(),
      "data"    => $this->payload_array()
    );
    
    return $out;
  
  }
  
  public function outputJSON() {
    return @json_encode($this->resultArray());
  }
  
  public function outputJSONP() {
    return $this->json_callback . '(' . $this->outputJSON($this->resultArray()) .')';
  }
  
}
