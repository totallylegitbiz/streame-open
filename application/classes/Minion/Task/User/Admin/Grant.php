<?

class Minion_Task_User_Admin_Grant extends Minion_Task {

    protected function _execute(array $params) {

      if (!count($params)) {
        die("NO EMAIL");
      }
      
      $email = $params[1];
      
      $user = Orm('user')->where('email','=', $email)->find();
      
      if (!$user->loaded()) {
        die("User not found");
      }
      
      $user->permission = 'ADMIN';
      $user->save();
      die('DONE');
    }
}