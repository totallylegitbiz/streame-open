<?

Class Cache_Remote  {

  var $database_id;
  var $redis;

  function __construct( $database_id = 0) {
    $this->database_id = $database_id;
    $this->redis       = $this->connect();
  }

  /**
   * Enter description here...
   *
   * @param unknown_type $database_id
   * @return Cache_Remote
   */

  static function instance ( $database_id = 0 ) {
   return new Cache_Remote($database_id);
  }

  function serialize ( $data ) {

    if (is_numeric($data)) {
      return $data;
    }
  
    return serialize($data);
  }
  
  function unserialize ($data) {
    
    if (is_null($data)) {
      return null;
    }
    
    if (is_numeric($data)) {
      return $data;
    }
    
    return @unserialize($data);
    
  }
  
  function connect() {

    static $redis_conn = Array();

    if (isset($redis_conn[$this->database_id])) {
      
      $connection = $redis_conn[$this->database_id];
      
      if (!$connection->isConnected()) {
        $connection->connect();
      }
      
      return $connection;
      
    }

    $servers    = explode(',',REDIS_SERVER);
    $connect_to = Array();
 
    list($host, $port) = explode(':', REDIS_SERVER);
    $connect_to = Array('host' => $host, 'port' => $port, 'database' => $this->database_id);
    
    $redis_conn[$this->database_id] = new Predis\Client($connect_to);
  
    return $this->connect();
  
  }

  function set( $key, $value, $timeout = 0 ) {
    
    if (is_array($key)) { $key = $this->keyify($key); }
    
    /*
if (IS_DEV && $this->database_id != REDIS_DATA_DB) { //REDIS_DATA_DB is for persistant data
      Logger::info('Cache_Remote::set(%s,%o,%s)', $key, $value, $timeout);
    }
    
*/
    $this->connect();
    
    $value = $this->serialize($value);
    
    if ((integer) $timeout > 0) {
      $r = $this->redis->setex($key, $timeout, $value);
    } else {
      
      if (IS_DEV) {
        Logger::error("Cache key set without a timeout: %s", $key);
      }
      
      $r = $this->redis->set($key, $value);
    }
    
    return $r;
    
  }
  
  function keyify($array) {
    return sha1(serialize($array));
  }
  
  function get ( $key, $unserialize = true ) {
  
    $this->connect();
    
    if (is_array($key)) { $key = $this->keyify($key); }
    
    $value = $this->redis->get($key);
      
    if ($unserialize) {
      $value = $this->unserialize($value);
    }
    
    if (IS_DEV) {
      //Logger::info('Cache_Remote::get(%s,%o)', $key, $value);
    }
    
    return $value;
    
  }
  
  function add( $key, $value, $timeout = 0) {
  
    if (is_array($key)) { $key = $this->keyify($key); }
    
    $this->connect();
    
    $value = $this->serialize($value);
    
    if (!$this->redis->setnx($key, $value)) {
    
      return false;
    }
    
    //Read: http://code.google.com/p/redis/issues/detail?id=140
      
    if ((integer) $timeout > 0) {
      $this->redis->expire($key, $timeout);
    }
    
    return true;
    
  }
  
  function exists ( $key ) {
  
    $this->connect();
    return $this->redis->exists($key);
  }
  
  function delete ( $key ) {
  
    $this->connect();
    return $this->redis->del($key);
  }
  
  
  function pdelete ( $pattern ) {
  
    $this->connect();
    $cnt = 0;
    
    foreach ($this->redis->keys($pattern) as $key) {
      $this->redis->del($key);
      $cnt++;
    }
    
    return $cnt;
  }
  
  function incr ( $key, $by = 1 ) {
  
    $this->connect();
    return $this->redis->incrby($key,$by);
  }
  
  function decr ( $key, $by = 1 ) {
  
    $this->connect();
    return $this->redis->decrbr($key,$by);
  }
    
}