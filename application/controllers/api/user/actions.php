<?

Class Controller_Api_User_Actions extends Api_Controller {
  var $needs_token = true;
  
  function action_post() {
    
    $target_id     = $this->request->param('target_id',   null, FILTER_VALIDATE_INT);
    $target_type   = $this->request->param('target_type', null, FILTER_VALIDATE_STR);
    $rating        = $this->request->param('rating',      0, FILTER_VALIDATE_INT); 
    $action        = $this->request->param('action',      null, FILTER_VALIDATE_STR_NOT_EMPTY); 

    $allowed_targets = [];
    
    Logger::debug("O: %o", $target_type);
    $action = $this->user->record_action(Orm($target_type, $target_id), $action, $rating);
    
    $this->response->payload = ['action'=>$action];
       
  }
  
}