<?

define('HAS_APC', function_exists('apc_fetch')?1:0);

/**
 * Local Memory Caching, faster than memcache since it does not need a socket call.
 *
 * Useful to load regularly needed files, such as the config.ini
 *
 */


Class Cache_Local {

  static function set ( $key, $var, $expire = 0) {

    try {
      if (!HAS_APC) return false;
  
      if ($var == null)
        return self::delete($key);
       
      return apc_store ($key, $var, $expire);
    } catch (Exception $e) {
      Logger::error("Error in apc_store: %o", $e);
      return false;
    }
  }

  static function get ( $key ) {
    if (!HAS_APC) return null;

    return apc_fetch ( $key );
  }

  static function add  ( $key, $var, $expire = 0) {
    if (!HAS_APC) return false;

    return apc_add ($key, $var, $expire);
  }

  static function delete ( $key ) {
    if (!HAS_APC) return false;

    return apc_delete($key);
  }
}
