<?

class Minion_Task_Database_Generate_Sql extends Minion_Task {

    protected function _execute(array $params) {
    
    
      $dbname = 'wooly';

      if (!mysql_connect('localhost', 'root', null)) {
          echo 'Could not connect to mysql';
          exit;
      }
      function show_create_table($table) {
      
        $sql = "SHOW CREATE TABLE $table";
        $result = mysql_query($sql);
        
        if (!$result) {
            echo "DB Error, could not list tables\n";
            echo 'MySQL Error: ' . mysql_error();
            exit;
        }
        
        while ($row = mysql_fetch_row($result)) {
          return $row[1];
        };
      
      }
      
      
      $sql = "SHOW TABLES FROM $dbname";
      $result = mysql_query($sql);
      
      if (!$result) {
          echo "DB Error, could not list tables\n";
          echo 'MySQL Error: ' . mysql_error();
          exit;
      }
      
      while ($row = mysql_fetch_row($result)) {
      
        $table = $row[0];
        
        $target_dir = str_replace('_', '/', $table);
      
        $target_file = APP_BASE . '/application/sql/tables/' . $target_dir .'.sql';
        $target_dir  = dirname($target_file);
        
        
        $create_sql = show_create_table($dbname . '.' . $table);
        $create_sql = preg_replace('/ AUTO_INCREMENT=[0-9]+ /',' ', $create_sql);
        
        `mkdir -p $target_dir`;
        
        file_put_contents($target_file, $create_sql);
      }
      
      
      mysql_free_result($result);      

    }   
}