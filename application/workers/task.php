#!/usr/bin/php
<?
define('APP_NAME', 'task');
define('DEFAULT_ERROR_HANDLER', true);
define('TASK_QUEUE_WORKER', true);
define('DELAY_LOG', false);
error_reporting( E_ALL ^ E_NOTICE);

require __DIR__ . '/../bootstrap.php';

define('WORKER_TIMEOUT', rand(5 * MINUTE, 10 * MINUTE)); //We will die in 300-400 seconds


$start_rev    = App::rev();
$task_php     = __FILE__;

$worker_id          = null;

if (isset($argv[1])) {
  
  if (!is_numeric($argv[1])) {
    throw new Except("Invalid CPU ID: %o", $argv[1]);
  }
  
  $worker_id = (integer) $argv[1];
  
}



//If this arg is set, we are a child.
if ($worker_id === null) {

  $process_name = 'task_queue';
  
  if(System_Process::instance($process_name)->is_running(true)) {
    Logger::error("Task queue is already running: %o - %o", $start_rev, $process_name);
    exit;
  }

  if (IS_DEV) {
    $process_count = System_Process::cpu_count() * 2;
  } else {
    $process_count = System_Process::cpu_count() * 2;
  }
  
  $processes = Array();
  
  $first_run = true;
  
  while(true) {
  
    for ($process_id = 0;$process_id<=$process_count - 1; $process_id++) {
      
      if (!$first_run && isset($processes[$process_id])) {
      
        if ($processes[$process_id]->status()) {
          continue;
        }
        
        if (System_Process::instance('task_queue_worker_' . $process_id)->is_running()) {
          continue; //It's running fine on it's own without us.
        }
      }
      
      sleep(1); 
      
      $exec = "$task_php $process_id " . App::rev();
      $processes[$process_id] = new System_Exec($exec);
      
      if (!$processes[$process_id]->status()) {
        Logger::error("WTF");
      }
      
      $processes[$process_id]->taskSet($process_id);
    }    
    if ($start_rev != App::rev(true)) {
      //Orm('log')->log('task_queue_master', null, 'INFO', "Revision changed, restarting: " . $start_rev . ' -> ' . App::rev());
      Logger::info("Revision changed, restarting master..");
      sleep(1);
      exit;
    }
    sleep(1);  
    $first_run = false;
    
 }
 exit;
}


$process_name = 'task_queue_worker_' . $worker_id;

if(System_Process::instance($process_name)->is_running(true)) {

  Logger::error("Worker is already running: %o / %o", $start_rev, $process_name);
  exit;
}

//If a fatal error is thrown, set this as an error and forget about it.
register_shutdown_function(Array("Task","shutdown"));

Logger::debug("Starting worker.. with rev %s - %s", $start_rev, $process_name);
//Orm('log')->log('task_queue_worker', $worker_id, 'INFO', "Starting worker " . App::rev());

  
$worker = Task::get_worker();

$timeout = time() + WORKER_TIMEOUT;

do {

  try {

    //Check to see if the revision has changed since we started...
    if ($start_rev != App::rev(true)) {

      Event::add('task_worker_job_restart', Array('worker_id' => $worker_id,'reason'=>'rev_change'));
      Logger::info("Revision changed, restarting worker..: %s", $process_name);

      sleep(1);
      exit;
    }
    
    //IT WASN'T MY TIME
    if (time() >= $timeout) {

      Logger::info("Worker timeout %s, restarting..",$process_name);

      Event::add('task_worker_job_restart', Array('worker_id' => $worker_id,'reason'=>'timeout'));
      sleep(1);
      exit;
    }
    
    //Let's see if we have any requests
    $start_mts = microtime(true);
    
    if ($r = @$worker->work()) {
    }
    
    $return_code = $worker->returnCode();

    if ($return_code == GEARMAN_SUCCESS) {
      Event::add('task_worker_job_exec', Array('worker_id' => $worker_id), round(microtime(true) - $start_mts,2));
      continue;
    }
    

    if (
        $return_code == GEARMAN_IO_WAIT 
      ||
        $return_code == GEARMAN_NO_JOBS
      ||
        $return_code == GEARMAN_TIMEOUT
      ) {
        //No jobs, let's just continue
        usleep(200 * 100); // 200ms
        continue;
      }
    
    if ($return_code  == GEARMAN_NO_ACTIVE_FDS)  { 
      Event::add('task_worker_job_restart', Array('worker_id' => $worker5id,'reason'=>'error_no_active_fds'));
      Logger::error("Got GEARMAN_NO_ACTIVE_FDS, sleeping then exiting...: %s", $process_name);
      //Orm('log')->log('task_queue_worker', $worker_id, 'ERROR', "Got GEARMAN_NO_ACTIVE_FDS, sleeping then exiting..." . App::rev());
      sleep(5);
      exit;
    }
    
  } catch (Exception $e) {
    

    Logger::error('Worker Exception: %s: %s', $e->getMessage(),$process_name);
    //Orm('log')->log('task_queue_worker', $worker_id, 'ERROR', "Job Exception:" . $e->getMessage());
    Event::add('task_worker_job_error', Array('worker_id' => $worker_id));
    exit;
  }
  

} while(1);
