<?

/*  */




class Minion_Task_Streame_Site_Poll extends Minion_Task {

  protected function _execute(array $params) {
  
    $sites = Orm('site')->find_all();
    foreach ($sites as $site) {
      Task::queue(function($site) use ($site){
        $site->poll();
      });
    }
  }
    
}