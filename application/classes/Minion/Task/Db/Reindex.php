<?

class Minion_Task_Db_Reindex extends Minion_Task {
    protected function _execute(array $params) {
     
      if (!isset($params[1])) {
        echo "Needs a table";
        exit;
      }
      
      $model = Orm($params[1]);
      
      $model->_map(true);
      
      $batch  = 1000;
      $page   = 0;
      $found  = true;
      while ($found) {
        $found = false;
        Logger::debug("Loading batch: %o", $page);
        foreach ($model->offset($batch*$page)->limit($batch)->order_by('id', 'DESC')->find_all() as $row) {
          $found = true;
          
          Task::queue(function($row){
            $row->update_index(); 
          }, $row);
        }
        Task::run_tasks();
        $page++;
      }
       
    }   
}
