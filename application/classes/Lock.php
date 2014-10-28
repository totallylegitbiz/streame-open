<?

Class Lock {

  var $cache_remote;
  var $key;
  var $ttl;
  
  function __construct( $lock_name, $ttl = 180) {
    $this->cache_remote = new Cache_Remote();  
    $this->key = 'LOCK::' . serialize($lock_name);
    $this->ttl = $ttl;
  }
  
  function lock () {
    return $this->cache_remote->add( $this->key, 1, $this->ttl);
  }
  
  function unlock() {
    return $this->cache_remote->delete($this->key);
  }
}
