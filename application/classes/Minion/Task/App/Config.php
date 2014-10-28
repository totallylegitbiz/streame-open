<?

class Minion_Task_App_Config extends Minion_Task {
    protected function _execute(array $params) {
      
      if (count($params)) {
        
        $pieces = explode('=', $params[1], 2);
        $var = $pieces[0];
          
        if (count($pieces) == 2) {
          $value = $pieces[1];
          App_Config::set($var, $value);
        } 
        
        echo $var . '=' . App_Config::get($var) . "\n";
        
        exit;
        
      }
      
      
      foreach (Orm('app_config')->find_all() as $config) {
        echo  $config->var . '=' . $config->value . "\n";
      }
      
    }   
}