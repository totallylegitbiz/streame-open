<?

Class Task {
  
  const PRIORITY_HIGH   = 1;
  const PRIORITY_NORMAL = 2;
  const PRIORITY_LOW    = 3;
  
  static $gearman = null;
  static $last_job;
  static $has_queued_tasks    = false;
  static $registered_shutdown = false;
  static $allow_task_within_a_task = true;
  
  static function get_worker() {
  
    $servers = explode(',', QUEUE_SERVERS);
    $working_servers = Array();
    
    foreach ($servers as $server) {
      
      $worker = new GearmanWorker();
      $worker->addServer($server);
      
      if (@$worker->echo('PINGO')) {
        $working_servers[] = $server;
      } else {
        Logger::error('Error with gearman server: %s', $server);
      }
    }
    
    if (!$working_servers) {
      throw new Exception("Unable to find any working servers: " . QUEUE_SERVERS);
    }
    
    $worker = new GearmanWorker();
    
    //Warning, uncommenting this line makes it so the timeout is never reached.
    $worker->addServers(implode(',', $working_servers));
    //$worker->addServer();
    $worker->setTimeout(1000);
    $worker->addFunction("task_process", "Task::process");
    
    Newrelic::add_custom_tracer("Task::process");
    
    //Logger::error('Worker with options: %s', $worker->options());
    
    return $worker;
  }
      
  static function get_client() {
    
    if (self::$gearman) {
      return self::$gearman;
    }
    
    $servers = explode(',', QUEUE_SERVERS);
    $working_servers = Array();
    
    foreach ($servers as $server) {
      
      $client = new GearmanClient();
      $client->addServer($server);
            
      if (@$client->echo('PINGO')) {
        $working_servers[] = $server;
      } else {
        Logger::error('Error with gearman server: %s', $server);
      }
    }
    
    if (!count($working_servers)) {
      throw new Exception('Unable to connect to gearman');
    }
    
    $client = new GearmanClient();
    $client->addServers(implode(',', $working_servers));
    //$client->addOptions(GEARMAN_CLIENT_NON_BLOCKING | GEARMAN_CLIENT_FREE_TASKS | GEARMAN_CLIENT_UNBUFFERED_RESULT);
    
    return self::$gearman = $client;
    
  }
  

  static function fork_and_wait($method, $arguments = Array()) {

    if (!is_array($arguments)){
      $arguments = Array($arguments);
    }
    
    $pid = pcntl_fork();
    
    if ($pid) {
      pcntl_waitpid($pid, $status);
      return $status;
    }
    
    try {
      call_user_func_array($method, $arguments);   
    } catch (Exception $e) {
      Logger::error('Forked Process Exception: %o', $e);
    }
    
    exit;
    
  }
  
  static function fork($method, $arguments = Array()) {
    
    if (!is_array($arguments)){
      $arguments = Array($arguments);
    }
    
    $pid = pcntl_fork();
    
    if ($pid) {
      return $pid;
    }
    
    try {
      call_user_func_array($method, $arguments);   
    } catch (Exception $e) {
      Logger::error('Forked Process Exception: %o', $e);
    }
    
    exit;
    
  }

  /*
    Queues to run later
  */
  
  static function queue ($method, $arguments = Array(), $priority = Task::PRIORITY_NORMAL) {
  
    //In the case that a task is forked from a task_queue process, just run it now instead.
    if (
        (defined('TASK_QUEUE_WORKER') && TASK_QUEUE_WORKER && self::$allow_task_within_a_task) ||
        (defined('RUNNING_TEST') && RUNNING_TEST)
      ) {
      //Logger::debug("Called within task, just executing");
      return call_user_func_array($method, $arguments);
    }
    
    if (!is_array($arguments)){
      $arguments = Array($arguments);
    }
    
    if ($method instanceof Closure) {
      $method = new Closure_Serializable($method);
    } else {
      throw new Except ("This has to be a Closure not - %o", $method);
    }
    
    $request = Array('method'=>$method, 'arguments'=>$arguments, 'ts'=>microtime(true), 'rev'=>App::rev());
  
    $uid     = sha1(microtime() . rand());
    $context = null;
    
    try {
      switch ($priority) {
        case Task::PRIORITY_HIGH:
           $task = self::get_client()->addTaskHighBackground('task_process', serialize($request), $context, $uid);
        break;
        case Task::PRIORITY_LOW:
          $task  = self::get_client()->addTaskLowBackground('task_process', serialize($request), $context, $uid);;
        break;
        default:
          $task  = self::get_client()->addTaskBackground('task_process', serialize($request), $context, $uid);;
      }
    } catch (Exception $e) {
      //Something happened when trying to add this job, just execute it now.
      Logger::error("Error adding to task queue: %o", $e);
      return call_user_func_array($method, $arguments);      
    }
    
/*     self::get_client()->runTasks(); */
    self::register_shutdown();
    self::$has_queued_tasks = true;
    
    self::run_tasks();
    
    return $task;
    
  }
  static function register_shutdown() {
  
    if (self::$registered_shutdown) {
      return;
    }
  
    register_shutdown_function(function() {
      Task::run_tasks();
    });
    
    self::$registered_shutdown = true;

  }
  
  static function run_tasks() {
  
    if (!self::$has_queued_tasks) {
      return;
    }
    
    self::get_client()->runTasks();
    
    self::$has_queued_tasks = false;
    
  }

  /*
    Run now and return the result
  */
  
  static function run ($method, $arguments = Array(), $catch_error = true) {
    
    //In the case that a task is forked from a task_queue process, just run it now instead.

    if (defined('TASK_QUEUE_WORKER') && TASK_QUEUE_WORKER && self::$allow_task_within_a_task) {
      return call_user_func_array($method, $arguments);
    }
    
    if ($method instanceof Closure) {
      $method = new Closure_Serializable($method);
    }
    
    if (!is_array($arguments)){
      $arguments = Array($arguments);
    }
    
    $request = Array('method'=>$method, 'arguments'=>$arguments);
    
    try {
      $result = self::get_client()->doNormal('task_process', serialize($request));
    } catch (Exception $e) {
      //Oh no, just execute
      Logger::error('Error running task: %o', $e);
      if (!$catch_error) throw $e;   
      return call_user_func_array($method, $arguments);
    }
    
    $result = unserialize($result);
    
    if (is_a($result, 'Exception')) {
      throw $result;
    }
        
    return $result;
    
  } 

  static function ping() {
    return hostname();
  }
  
/*
  static function process(GearmanJob $job) {
    self::fork_and_wait(function($job) {
      Task::_process($job);
    }, $job);
  }
*/
  
  static function process(GearmanJob $job, $fork = true) {
    
    if ($fork) {
      return self::fork_and_wait(function() use ($job) {
        Task::process($job, false);
      });
    }
    
    self::$last_job = $job;
    
    try {
      $request  = unserialize($job->workload());
      $start_ts = microtime(true);
      
      Newrelic::begin_transaction('task');
      $result   = call_user_func_array($request['method'], $request['arguments']);
    
      //Time to execute the queue
      Event::add ( 'queue_delay',   Array('host'=>hostname()), round(($start_ts-ifSet($request['ts'],0))*1000));
      //Time to process
      Event::add ( 'queue_process', Array('host'=>hostname()), round((microtime(true) - $start_ts)*1000));

      self::$last_job = null;
      $result = serialize($result);
      $job->sendComplete($result);
      Newrelic::end_transaction();
      return $result;
      
    } catch (Exception $e) {
      Logger::error('Worker Exception: %o', $e);
      self::$last_job->sendFail();
      self::$last_job = null;
      Newrelic::error('Worker Exception', $e);
      Newrelic::end_transaction();
      return false;
    }
    
  }

  static function shutdown() {
  
    if (self::$last_job) {
      Logger::error("Got an unfinished job, setting as error");
      self::$last_job->sendFail();
    }
    
    if ($error = error_get_last()) {
      if ($error['type'] == E_ERROR) {
        Logger::error("Fatal Error, dying: %o", $error);
      }
    }
  }

}
