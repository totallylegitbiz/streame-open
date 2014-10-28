<?

class Minion_Task_Database_Generate_Models extends Minion_Task {

    protected function _execute(array $params) {

      $skip = ['sessions'];
      
      $db = "streame";      
      $mysqli = new mysqli("localhost", "root", "", $db);
      
      /* check connection */
      if ($mysqli->connect_errno) {
          printf("Connect failed: %s\n", $mysqli->connect_error);
          exit();
      }
      
      $result = $mysqli->query("show tables");
      
      $ignore = Array();
      
      $plural_exceptions = Array('event_series');
      $col = 'Tables_in_' . $db;
      
      while ($row = $result->fetch_assoc()){
        $table = $row[$col];
        
        if (in_array($table, $ignore)) {
          return;
        }
        
        if (in_array($table, $skip)) {
          continue;
        }
        
        if (!in_array($table, $plural_exceptions)) {
          $table = preg_replace('/(ies)$/', 'y', $table);
          $table = preg_replace('/(s)$/', '',    $table);
        }
        
        $file_path = CLASS_BASE . '/Model/' . str_replace(' ', '/', ucwords(str_replace('_', ' ', $table))) . '.php';
  
        if (file_exists($file_path)) {
          continue;
        }
        
        #die('dont use this until you fix this chmod');
        @mkdir(dirname($file_path),0775,true);
        
        $model_name = str_replace(' ', '_', ucwords(str_replace('_', ' ', $table)));
        
        $content = "<?
     
class Model_{$model_name} extends Orm {      
}";
      
        file_put_contents($file_path, $content);
        echo "Creating Model_$model_name - $file_path";
      }
      

    }   
}
