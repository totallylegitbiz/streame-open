<?

Class Interval {
  
  static function cron($expression, $name, $function, $parameters = Array()) {

    $cron = Cron\CronExpression::factory($expression);
    
    $curr_ts = new DateTime();
    $curr_ts->setTimestamp(INTERVAL_START_TS);
    
    $next_ts = $cron->getNextRunDate($curr_ts)->getTimestamp();
    
    if (!$cron->isDue($curr_ts)) {
      //Orm('log')->log('interval', $name, 'DEBUG', "Not yet, next run in: " . ($next_ts - INTERVAL_START_TS));
      return; //Not our time.
    }
    
    $lock           = new Lock(Array('INTERVAL', __CLASS__, __METHOD__, $name,floor(INTERVAL_START_TS / MINUTE)), $next_ts);
    
    if (!$lock->lock()) {
      Logger::debug("Interval %o already ran, skipping run", $name);
      
      //Orm('log')->log('interval', $name, 'ERROR', "Interval already ran, sure interval isn't running more often than it should?");
      return false;
    } 
    
    //Orm('log')->log('interval', $name, 'INFO', "Starting interval next_ts:" . $next_ts);
    
    $running_lock   = new Lock(Array('RUNNING_LOCK',__CLASS__, __METHOD__, $name), ($next_ts - INTERVAL_START_TS));
    
    if (!$running_lock->lock()) {
      Logger::debug("Still running from before...%o", $name);
      //Orm('log')->log('interval', $name, 'ERROR', "Unable to get lock, still running from before");
      return false;
    } 
    
    $function       = new Closure_Serializable($function);
    
    Logger::debug("Running interval: %o", $function);
    
    //Orm('log')->log('interval', $name, 'INFO', "Running");
    
    Newrelic::begin_transaction($name);
    
/*     return Task::queue(function($running_lock,$function, $parameters, $name){ */
    
      try { 
        //Orm('log')->log('interval', $name, 'INFO', "Processing begin");
        $function($parameters);
        //Orm('log')->log('interval', $name, 'INFO', "Process complete, unlocking");
        $running_lock->unlock();
      } catch (Exception $e) {
        $running_lock->unlock();
        Logger::error('Error executing interval: %o', $e);
        //Orm('log')->log('interval', $name, 'ERROR', $e->getMessage());
      }
    Newrelic::end_transaction();
/*     }, Array($running_lock,$function, $parameters, $name)); */
    
    
  }
  
/*
  static function every($key, $interval, $function, $parameters = Array()) {
    
    if (INTERVAL_START_TS % $interval) { return; }; 
  
    $function       = new Closure_Serializable($function);
    
    //Makes sure it only runs once per interval
    $snap_lock      = new Lock(Array(__CLASS__, __METHOD__, $key, floor(INTERVAL_START_TS / $interval)), $interval);
    
    
    if (!$snap_lock->lock()) {
      Logger::error("Interval running before it's time, sure interval isn't running too often?");
      return false;
    }
    
    $lock           = new Lock(Array(__CLASS__, __METHOD__, $key), $interval);
    
    if (!$lock->lock()) {
      Logger::debug("Interval %o still running, skipping run", $key);
      return false;
    } 
    //Makes sure that if the previous one didn't run, don't run again.
    $repeat_lock    = new Lock(Array(__CLASS__, __METHOD__, $key,'repeat'), 5 * HOUR);
    
    if (!$repeat_lock->lock()) {
      Logger::error("Last interval %o didn't run, sure there isn't a problem?", $key);
      return false;
    }
    
    Logger::debug("Running interval: %o", $function);
    
    //return Task::fork(function($lock, $repeat_lock,$function, $parameters){
    
      $repeat_lock->unlock();
      
      try { 
        $function($parameters);
      } catch (Exception $e) {
        Logger::error('Error executing interval: %o', $e);
      }
    
      $lock->unlock();
      
    //}, Array($lock, $repeat_lock,$function, $parameters));
   
  }
*/
  
 /*
 static function at($time, $function, $parameters = Array(), $priority = Task::PRIORITY_HIGH) {
    
    $time  = floor(strtotime($time, INTERVAL_START_TS) / 60) * 60;

    if ($time != INTERVAL_START_TS) { return; }
    
    $function       = new Closure_Serializable($function);
    
    //Makes sure it only runs once per interval
    $lock           = new Lock(Array(__CLASS__, __METHOD__, $time,$function,$parameters), MINUTE);
    
    if (!$lock->lock()) {
      Logger::error("Interval running before it's time, sure interval isn't running too often?");
      return false;
    }
  
    //return Task::fork(function($function, $parameters){
    
      try { 
        $function($parameters);
      } catch (Exception $e) {
        Logger::error('Error executing interval: %o', $e);
      }
    
      
    //}, Array($function, $parameters));
    
  } 
*/
  
}
