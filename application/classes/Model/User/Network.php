<?

class Model_User_Network extends Orm {

   var $_serialize_columns = Array('meta');
   
   protected $_belongs_to = [
    'user'      => ['model'=>'user','foreign_key'=>'user_id']
   ];
  
   function needs_fb(){
     if ($this->network != 'FACEBOOK') {
       throw new Exception('Only works for facebook buddy');
     }
   }
   function fb_has_perm($perm = null) {
     
     $this->needs_fb();
       
     $facebook = new Facebook_Api();
     return $facebook->has_perm($this->network_user_id, $perm);
         
   }
   
/*
   function fb_chat_message($to_uid, $message) {
      $this->needs_fb();
       
      Facebook_Chat::send_message(
        $this->network_user_id, 
        $this->network_token,
        $to_uid,
        $message
      );
   }
*/
   
}