<?

/**
 * Used for global config, wire on/off abilties
 * TODO: Use one key in Cache_Remote for all the configs instead of one.
 */
 
/*
 CREATE TABLE `app_configs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `var` varchar(255) NOT NULL,
  `value` text,
  `description` varchar(255) DEFAULT NULL,
  `is_hidden` tinyint(1) DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `var` (`var`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8

*/

Class App_Config {

  /**
   * The local cache guarantees that two calls for the same key on the same request returns the same thing
   */

  static $_local_cache  = null;
  static $_cache_key    = 'APP_CONFIG';
  
  /**
   * Grabs the cache array from the database
   *
   */
  
  static function init( $force = false ) {
    
    if (!$force && self::$_local_cache !== null) {
      return;
    }  
    
    //Let's try APC first;
    self::$_local_cache = Cache_Local::get(self::$_cache_key);
    
    if (!$force && self::$_local_cache) {
      return;
    }
    
    self::$_local_cache = Array();
    
    foreach (Orm('app_config')->find_all() as $config) {
      self::$_local_cache[$config->var] = $config->value;
    }
    
    //Set the cache back, but only for 5 seconds.
    Cache_Local::set(self::$_cache_key, self::$_local_cache, 5);
      
    return self::$_local_cache;
  
  }
  
  /**
   * Set a global config value and store it in both in process and remote caches.
   *
   * @param string $var
   * @param string $default_val
   * @return mixed
   */

  static function get ( $var, $default_val = null ) {

    self::init();
    
    if (!key_exists($var,self::$_local_cache)) {
      self::init(true); //Force a read for new key
    }
    
    if (!key_exists($var,self::$_local_cache)) {
      //Whoa, doesn't exist, let's force a set to null.
      self::set($var, null);
      return null;
    }
    return self::$_local_cache[$var];
   
  }

  /**
   * Set a global config value.
   *
   * @param string $var 
   * @param string $value
   */

  static function set ( $var, $value ) {

    $config = Orm('app_config')->where('var', '=', $var)->find();
    
    //Not found, throw exception
    if (!$config->loaded()) {
      $config->var = $var;
      Logger::debug('Config var not found, creating one for %o', $var);
    }

    $config->value = $value;
    
    $config->save();
    
    self::init(true);

    return;

  }
  
  static function delete($var) {
    Orm('app_config')->where('var', '=', $var)->find()->delete();
    self::init(true);
  }

}
