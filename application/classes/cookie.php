<?

class Cookie extends Kohana_Cookie {

  static function decode ($key, $value) {
  
    $split = strlen(self::salt($key, NULL));
    
    if (isset($value[$split]) AND $value[$split] === '~') {
    
      // Separate the salt and the value
      list ($hash, $value) = explode('~', $value, 2);
      
      if (self::salt($key, $value) === $hash) {
        // Cookie signature is valid
        return $value;
      }
      
    }
    
    return; 
  }
  
  /**
     * Generates a salt string for a cookie based on the name and value.
   *
   *     $salt = Cookie::salt('theme', 'red');
   *
   * @param   string   name of cookie
   * @param   string   value of cookie
   * @return  string
   */
   
  public static function salt($name, $value)
  {
    // Determine the user agent
    // $agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : 'unknown';
    return sha1( /* $agent. */ $name.$value.Cookie::$salt);
  }
  
}
