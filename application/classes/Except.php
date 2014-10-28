<?

Class Except extends Exception {

 function __construct() {

    $args  = func_get_args();
    
    $args[0] = str_replace('%o', '%s', $args[0]);
    
    for($i=1;$i<count($args);$i++) {
      if (is_object($args[$i]) || is_array($args[$i])) {
        if ($args[$i] instanceof Exception) {
          $args[$i] = "Exception:" . $args[$i]->getMessage() . ' in ' . $args[$i]->getFile().' [' . $args[$i]->getLine(). ']';
        } else {
          $args[$i] = @json_encode($args[$i]);
        }
      }
    }
  
    $this->message  =  call_user_func_array("sprintf", $args); 
  }
  
}