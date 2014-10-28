<?

Class Controller_Admin_Beta extends Controller_Admin {
  
  public function action_invites () {
  
    $page      = $this->request->query('page');
    
    if (!$page) { $page = 1;}
    
    $per_page  = 200;
    
    
    $beta_applications      = Orm('beta_application')->offset(($page-1)*$per_page)->limit($per_page)->find_all();
    $beta_application_count = Orm('beta_application')->count_all();
    
    
    $this->assign('_beta_application_count', $beta_application_count);
    $this->assign('_beta_applications', $beta_applications);
    
    $this->assign('_pages', ceil($beta_application_count / $per_page));
    $this->assign('_page',  $page);


    $this->assign('_applications', Orm('beta_application')->find_all());
    $this->render('beta/list.html');
  }
  
}