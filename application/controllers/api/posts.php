<?

Class Controller_Api_Posts extends Api_Controller {
/*   var $needs_token = true; */
  
  function action_get( $post_id = null ) {
    
    if ($post_id) {
      $post = Orm::site_post()->where('id','=',$post_id)->find();
      return $this->response->payload = ['post'=>$post];
    }
    
    
    $offset     = $this->request->param('offset', 0,  FILTER_VALIDATE_INT);
    $limit      = $this->request->param('limit',  10, FILTER_VALIDATE_INT); 
    
    $this->check_token(true);
    
    if ($this->user) {
      $this->response->payload = ['posts'=>Orm::site_post()->get_recommended($this->user,$limit, $offset)];
    } else {
      die('LOGING PLEASE');
    }
    
  }
}