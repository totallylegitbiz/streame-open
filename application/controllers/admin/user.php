<?

Class Controller_Admin_User extends Controller_Admin {
  
  public function action_index () {
  
    $user_id      = $this->request->param('id');
    
    if ($user_id) {
      return $this->handle_detail($user_id);
    }
    $page      = $this->request->query('page');
    
    if (!$page) { $page = 1;}
    
    $per_page  = 200;
    
    $users      = Orm('user')->offset(($page-1)*$per_page)->limit($per_page)->find_all();
    $user_count = Orm('user')->count_all();
    
    
    $this->assign('_user_count', $user_count);
    $this->assign('_users', $users);
    
    $this->assign('_pages', ceil($user_count / $per_page));
    $this->assign('_page',  $page);

    $this->assign('_users', Orm('user')->find_all());
    $this->render('user/list.html');
    
  }
  
  function handle_detail($user_id) {
  
    $user = Orm('user', $user_id);
    
    if (!$user->loaded()) {
      return $this->redir('/user');
    }
    
    if ($post = $this->request->post()) {
      $user->set_array($post);
      $user->save();
    }
    
    $this->assign('_user_detail', $user);
    $this->render('user/detail.html');
  }
  
}