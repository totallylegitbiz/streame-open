<?

/*
function readline($sec, $def = null) 
{ 
    return trim(shell_exec('bash -c ' . 
        escapeshellarg('phprlto=' . 
            escapeshellarg($def) . ';' . 
            'read -t ' . ((int)$sec) . ' phprlto;' . 
            'echo "$phprlto"'))); 
} 
*/

class Minion_Task_User_Password extends Minion_Task {

    protected function _execute(array $params) {

      if (!count($params)) {
        die("NO EMAIL");
      }
      
      $email = $params[1];
      
      $user = Orm('user')->where('email','=', $email)->find();
      
      if (!$user->loaded()) {
        die("User not found");
      }
      
      echo "Enter Password:"; ob_flush();
      $password = readline(60);
     
      $user->set_password($password);
      
    }
    
}