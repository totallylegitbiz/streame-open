<?

Class Controller_Api_Index extends Api_Controller {

  function action_get() {

    $this->response->payload = 'Tis Legit!';
    
  }
  
}