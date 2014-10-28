<?

use PredictionIO\PredictionIOClient;

class Model_User extends Orm {
  
  protected $_belongs_to = [
    'avatar'      => ['model'=>'image','foreign_key'=>'avatar_image_id']
  ];
  
  protected $_has_many = Array(
    'practice_roles' => Array('model'=>'practice_role','foreign_key'=>'user_id'),
    'practices'      => Array('model'=>'practice', 'through'=>'practice_roles','foreign_key'=>'user_id'),
    'patients'       => Array('model'=>'patient',  'foreign_key'=>'user_id', 'through' => 'user_patients'),
  );
  
  function display_name() {
    if ($this->display_name) {
      return $this->display_name;
    }  
    
    return $this->email;
  }
  
  public function sanitize_filters() {
    return 
    [
      'display_name' => [
        [FILTER_VALIDATE_STR_NOT_EMPTY, null, 'Name cannot be empty'],
       ],
      'email' => [
        [FILTER_VALIDATE_EMAIL, null, 'Name cannot be empty'],
       ]
    ];
  }
  
  function record_action($target, $action, $rating = null) {
    $actions = ["rate", "like", "dislike", "view","conversion"];
    
    if (!in_array($action, $actions)){
      throw new Exception('Invalid action');
    }
    
    if ($action == 'rate' && !($rating >= 1 && $rating <= 5)) {
      throw new Exception('Rating must be between 1 and 5');
    }
    if (!$target->loaded()) {
      throw new Exception('Object not loaded');
    }
    
    $new_action = Orm('user_action');
    $new_action->target_type = $target->get_object_name();
    $new_action->target_id   = $target->id;
    $new_action->user_id     = $this->id;
    $new_action->action      = $action;
    $new_action->rating      = $rating;
    $new_action->save();
    $new_action->reload();
    
    Orm::user_taste()->recalculate($this);
    
    Task::queue(function() use ($new_action){
      $new_action->propagate();
    });
    
    return $new_action;
  }
  
  function register ( $email, $password, $display_name = null ) {
    
    //TODO: Validate
    $salt = sha1(microtime() . rand());
    
    $user = new Model_User();
    
    $hash = $user->generate_hash($password, $salt);
  
    $user->email         = $email;
    $user->password_hash = $hash;
    $user->password_salt = $salt;
    $user->display_name  = $display_name;       
    
    try {
      $user->save_reload();
    } catch (Database_Exception $e) {
      if ($e->getCode() != 1062) {
        throw $e;
      }
      
      $e = new Orm_Filter_Exception();
      $e->errors = ['email'=>'Email already in use. <a href="https://' . BASE_DOMAIN . '/u/forgot">Forgot your password?</a>'];
      throw $e;
    }
    
/*
    Task::queue(function($user) {
      $user->_pio_create();
    }, $user);
*/
    
    return $user;
  }
  
  function _pio_create() {
  
    $client =  PredictionIOClient::factory([
      'appkey' => PREDICTIONIO_API_KEY,
      'apiurl' => PREDICTIONIO_API_ENDPOINT
    ]);

    $command  = $client->getCommand('create_user', array('pio_uid' => $this->id));
    $client->execute($command);
  }
  
  function set_password($password) {
    $salt = sha1(microtime() . rand());
    $hash = $this->generate_hash($password, $salt);
    
    $this->password_hash = $hash;
    $this->password_salt = $salt;
    $this->save();
  }
  
  function by_login( $email, $password ) {
    
    $user = Orm('user')->where('email','=', $email)->find();
    Logger::debug("Found %o", $user->id);
    if (!$user->loaded() || $this->generate_hash($password, $user->password_salt) !== $user->password_hash) {
      return;
    }
    
    return $user;
    
  }
  
  function by_session_token ($token) {
  
    $session      = Session::instance('Database_Cookieless');
    
    $session->read($token);
    
    if (!$user_id = $session->get('user_id')) {
      return;
    }
      
   
    $user = Orm('user',$user_id);

    if (!$user->loaded()) {
      return;
    }
    
    return $user;
    
  }
  
  function generate_session() {
  
    $session  = Session::instance('Database_Cookieless');
    
    $session->set('user_id', $this->id);
    $session->write();
    
    return $session->id();
    
  }
  
  function generate_hash ( $password, $salt ) {
    return sha1($password . '::' . $salt . ' oh no not bath salts!');
  }
  
  function avatar_src() {
    $fb = Orm('user_network')
            ->where('user_id', '=', $this->id)
            ->where('network', '=', 'FACEBOOK')
            ->find();
    if ($fb->loaded()) {        
      return '//graph.facebook.com/' . $fb->network_user_id . '/picture';
    }
  }
  
  function create_from_fb_token($access_token) {
    
    $user_network = Orm('user_network')
           ->where('network',      '=', 'FACEBOOK')
           ->where('network_token','=', $access_token)
           ->find();
    
    if ($user_network->loaded()) {
      return $user_network->user;
    }
    
    $facebook     = new Facebook_Api();
    $user_details = $facebook->api('/me', 'GET', ['access_token'=>$access_token]);
  
    Logger::debug("Facebook Data: %o", $user_details);
    //Let's first see if this user connected in the past;

    $user_network = Orm('user_network')
           ->where('network',       '=', 'FACEBOOK')
           ->where('network_user_id','=', $user_details['id'])
           ->find();
    
    if ($user_network->loaded()) {
      return $user_network->user;
    }
      
    //Let's see if we have this user via an email match
    

    $user = Orm('user')->where('email','=',$user_details['email'])->find();
    
    if (empty($user_details['email']) || !$user->loaded()) { 
      //Guess no one has registered with this.
      $user = Orm('user')->register($user_details['email'], null, $user_details['name']);
    }
          
    $token = Orm('user_network')
               ->where('user_id', '=', $user->id)
               ->where('network', '=', 'FACEBOOK')
               ->where('network_user_id','=', $user_details['id'])
               ->find();
               
    $token->network         = 'FACEBOOK';
    $token->network_token   = $token;
    //$token->expires         = $expires;
    $token->meta            = $user_details;
    $token->network_user_id = $user_details['id'];
    $token->user_id         = $user->id;
 
    $token->save();
    
    return $user;
        
  }
  
  function check_perms($model, $id) {
      
    $model = Orm($model, $id);
    
    if (!$model->loaded()) {
      return;
    }
    
    if (!$model->has_perms($this)) {
      return;
    }
      
    return $model;
    
  }
  
  function add_patient(Model_Patient $patient) {
    $user_patient = Orm('user_patient')
                           ->where('user_id','=', $this->id)
                           ->where('patient_id','=', $patient->id)
                           ->find();
                        
    if (!$user_patient->loaded()) {
      $user_patient->user_id    = $this->id;
      $user_patient->patient_id = $patient->id;
    }
    
    $user_patient->save();
    return $user_patient;
  }
  
  function to_array($extra_fields = []){
    
    return Array(
      'id'              => (integer) $this->id,
      'display_name'    => $this->display_name,
      'email'           => $this->email,
      'avatar'          => Array('src' => $this->avatar_src())
    );
    
  }
  
}