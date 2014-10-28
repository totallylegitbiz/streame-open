<?

Class System_Process {
  
  protected $filename;
  protected $created_pid = false;
  
  static function instance ($process_name) {
    return new System_Process($process_name);
  }

  function __construct($process_name) {      
    $pid_file       = sys_get_temp_dir() . '/' . $process_name.'.pid';
    $this->filename = $pid_file;
  }
    
  static function pid_running($pid) {
    return posix_kill($pid, 0);
  }
  
  function is_running ($overwrite = false) {
  
    if(file_exists($this->filename)) {
      
      $pid = (int)trim(file_get_contents($this->filename));
      
      if ($this->pid_running($pid)) {
        return true;
      }
    } 
    
    if (!$overwrite) {
      return false;
    }
  
    $pid = getmypid();
    
    file_put_contents($this->filename, $pid);

    $this->created_pid = true;
    
    register_shutdown_function( function ($file) { @unlink($file); }, $this->filename );
    
    return false;

  }
  
  static function cpu_count() {
  
    static $count = null;
    
    if ($count) {
      return $count;
    }
    
    exec('cat /proc/cpuinfo | grep processor | wc -l',$processors);
    
    return $count = $processors[0];
  }
  
  static function taskset ( $cpu, $pid) {
   
    if ($cpu > self::cpu_count()) {
      $cpu = $cpu % self::cpu_count();
    }
    return exec('taskset -pc ' . $cpu . ' ' . $pid);
  }
  
  static function fork_loop($children, $exit_on_revision) {
  
    $pids      = Array();
    $start_rev = rev();
    
    while(true) {
      for ($i=0;$i<$children;$i++) {
      
        $pids[$i] = $pid = pcntl_fork();
        
        if ($pid == -1) {
          Logger::error("Forking error, exiting");
          exit;
        }
        
        if ($pid) {
          $cpu = $i % self::cpu_count();
          self::taskset($cpu, $pid);
          
          Logger::debug('Forked process %o, set CPU affinity to %o', $pid, $cpu);   
          continue;
        } else {      
          //I AM CHILD, I'M OUTTA HERE
          //Logger::info('I AM BABY');
          return;
        }
      }
      
      for ($i=0;$i<$children;$i++) {
        pcntl_waitpid($pids[$i], $status, WUNTRACED);
      }
      
      if ($exit_on_revision && $start_rev != rev(true)) {
        Logger::info("Revision changed, restarting worker..: %s", $process_name);
        exit;
      }
      
    }
  }

   
}
