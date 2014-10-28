<?

Class Controller_Api_Ping extends Api_Controller {

  function action_get() {
    
    if ($this->request->param('fail')) {
      throw new Api_Exception("This failed on purpose");
    }
    
    $this->response->payload = 'PONG';
    
  }
  
}

