<?

class System_Exec {

    private $pid;
    private $command;

    public function __construct($cl=false){
      if ($cl != false){
        $this->command = $cl;
        $this->runCom();
      }
    }
    private function runCom(){
      $command = 'nohup '.$this->command.' > /dev/null 2>&1 & echo $!';
      exec($command ,$op);
      $this->pid = (int)$op[0];
    }

    public function setPid($pid){
      $this->pid = $pid;
    }

    public function getPid(){
      return $this->pid;
    }

    public function taskset($cpu) {
      return System_Process::taskset($cpu, $this->pid);
    }
    
    public function status(){
      return System_Process::pid_running($this->pid);
    }

    public function start(){
      if ($this->command != '')$this->runCom();
      else return true;
    }

    public function stop(){
      $command = 'kill '.$this->pid;
      exec($command);
      if ($this->status() == false)return true;
      else return false;
    }
}