<?

Interval::cron('0 9 * * * *', 'daily_post_email',   function(){
return;
  $vars = Array(
    '_subject' => 'Your daily email of amazingness',
    '_posts'   => Orm('site_post')
                      ->order_by('score','DESC')
                      ->where('status','=','ACTIVE')
                      ->where('created','>', new Database_Expression('DATE_SUB(now(),INTERVAL 1 DAY)'))
                      ->limit(15)
                      ->find_all()
    
  );
  
  Email::send_template ( 'jorge@spright.ly',  'daily/best_of', $vars);
  Email::send_template ( 'pamela@spright.ly', 'daily/best_of', $vars);
    
});
