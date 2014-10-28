<?

Class Newrelic {

  static function is_installed() {
    return extension_loaded('newrelic');
  }
  
  static function set_app_name($appname = APP_NAME, $project = APP_PROJECT) {
    if (!self::is_installed()) return false;
    
    newrelic_set_appname($project . ' - ' . APP_NAME);
  }
  
  static function metric($key, $value) {
    if (!self::is_installed()) return false;
    newrelic_custom_metric($key, $value);
  }
  
  static function background_job ( $is_background = true ) {
    if (!self::is_installed()) return false;
    newrelic_background_job($is_background);
  }
  
  static function custom_param($key, $value) {
    if (!self::is_installed()) return false;
    newrelic_add_custom_parameter($key, $value);
  }
  
  static function name_transaction($name) {
    if (!self::is_installed()) return false;
    newrelic_name_transaction($name);
  }  
  static function capture_params($capture = true) {
    if (!self::is_installed()) return false;
    newrelic_capture_params($capture);
  }
  
  static function add_custom_tracer($func) {
    if (!self::is_installed()) return false;
    newrelic_add_custom_tracer($func);
  }
  
  static function begin_transaction($name, $appname = null) {
    if (!self::is_installed()) return false;
        
    if (!$appname) {
      $appname = APP_PROJECT . ' - ' . APP_NAME;
    }
    
    newrelic_start_transaction($appname);
    self::name_transaction($name);
    
  }
  static function end_transaction($ignore = false) {
    if (!self::is_installed()) return false;
    newrelic_end_transaction($ignore);
  }
  static function error($message, $exception) {
    if (!self::is_installed()) return false;
    newrelic_notice_error($message,$exception );
  }

}