<?


Class Logger {

  static $log_level = null;
  
  static function level () {
    
    if (self::$log_level) {
      return self::$log_level;
    }
    
    return self::$log_level = IS_DEV?LOG_INFO:LOG_ERR;
  }
  
  static function info () {
    //if (self::level() >= LOG_INFO)
    $message = self::create_message(func_get_args(), LOG_INFO);
  }

  static function debug () {
    //if (self::level() >= LOG_DEBUG)
    $message = self::create_message(func_get_args(), LOG_DEBUG);
  }

  static function error () {
    $message = self::create_message(func_get_args(), LOG_ERR);
  }

  static function write_log($message, $level) {
  
    static $registered_shutdown = false;
    			      
    switch ($level) {
      case LOG_INFO:
        $syslog_level = LOG_INFO;
        $syslog_msg   = "INFO";
        $color = Console::CF_YELLOW;
      break;
      case LOG_DEBUG:
        $syslog_level = LOG_DEBUG;
        $syslog_msg   = "DEBUG";
	        $color = Console::CF_GREY;
      break;
      case LOG_ERR:
        $syslog_level = LOG_WARNING;
        $syslog_msg   = "WARNING";
  	     $color = Console::CF_RED ;
      break;
      default:
        $syslog_level = LOG_NOTICE; 
        $syslog_msg   = "NOTICE";
        $color        = Console::CF_RED ;
    }
    
    /* Does the syslog writing */
    
    if (!$registered_shutdown) {
      openlog(SYSLOG_NAME, LOG_PID | LOG_PERROR, LOG_LOCAL0);
      register_shutdown_function('closelog');
      $registered_shutdown = true;
    }
    
    syslog($syslog_level, "${syslog_msg}: ${message}");
    
    /** Write to file **/
    
    $line = Console::encode ( $message, $color);

    $filename = LOGBASE .  '/debug.log';
    
    if ( ! file_exists($filename)) {
			touch($filename);
			// Allow anyone to write to log files
			chmod($filename, 0666);
		}
		
		// Write each message into the log file
		file_put_contents($filename, date(DATE_ATOM) . ' ' . $line . PHP_EOL, FILE_APPEND);
    
  }  
  
  static function create_message($args, $level) {
    
   
		/* Create the backtrace */
		
		$db = debug_backtrace();
    
    if (isset($db[2])) {
    
      $bt = $db[2];
      
      $function = '';
      
      if (isset($bt['function'])) {
        $function = isset($bt['class'])?$bt['class'] .'::'. $bt['function']:$bt['function'];
        $function .='()';
        
      } else if (isset($bt['function'])) {
        $function = $bt['file'];
      } 
       
      $line = ifset($bt['line'],'X');     
      
      $append = " ~ ${function} [ ${line} ]";
      
    } else  {
      $append = '';
    }
    
    /* Do the replacement variable stuff to mimic console.log */
    
    if (count($args) == 1) {
      $message = $args[0];
    } else {

      $args[0] = str_replace('%o', '%s', $args[0]);

      for ($i = 1; $i<count($args);$i++) {
        if (is_object($args[$i]) || is_array($args[$i])) {
        
          if ($args[$i] instanceof Exception) {
            $args[$i] = "Exception:" . $args[$i]->getMessage() . ' in ' . $args[$i]->getFile().' [' . $args[$i]->getLine(). ']';
          } else {
            $args[$i] = @json_encode($args[$i]);
          }
        }
      }

      $message =  call_user_func_array("sprintf", $args);

    }

    $message .= $append . ' @' . hostname(false);

    if (defined('APP_NAME')) {
      $message = '['.APP_NAME.'] ' . $message;
    }
    
    $message .= ' [' . getmypid() . ']';
    
    self::write_log($message, $level);
          
  }


}