<?

Interval::cron('17 * * * * *', 'site_post_score_polling',  function(){
  foreach (Orm('site_post')
            ->order_by('score','desc')
            ->where('status','=','ACTIVE')
            ->where('created','>', new Database_Expression('DATE_SUB(now(),INTERVAL 2 DAY)'))
            ->find_all() as $post) {
            
    Task::queue(function($post) {
      try {
        $lock = new Lock(Array('site_post_score_polling:post', $post->id), 2 * HOUR);
        
        if (!$lock) {
          return;
        }
        
        Orm('score')->poll($post);
        $post->update_score();
        $post->_index();
        foreach (Orm('site_post_image')->where('src','!=','')->where('site_post_id','=', $post->id)->find_all() as $post_image){
          Orm('score')->poll($post_image);
        }
      } catch (Exception $e) {
        Logger::error("Exception: %o", $e);
      }
    }, $post, Task::PRIORITY_LOW);
  }  
});

Interval::cron('37 4 * * * *', 'site_old_post_score_polling',  function(){
  foreach (Orm('site_post')
            ->order_by('score','desc')
            ->where('status','=','ACTIVE')
            ->where('created','<', new Database_Expression('DATE_SUB(now(),INTERVAL 2 DAY)'))
            ->where('created','>', new Database_Expression('DATE_SUB(now(),INTERVAL 9 DAY)'))
            ->find_all() as $post) {
    Task::queue(function($post) {
      try {
        Orm('score')->poll($post);
        $post->update_score();
        $post->_index();
        foreach (Orm('site_post_image')->where('src','!=','')->where('site_post_id','=', $post->id)->find_all() as $post_image){
          Orm('score')->poll($post_image);
        }
      } catch (Exception $e) {
        Logger::error("Exception: %o", $e);
      }
    }, $post, Task::PRIORITY_LOW);
  }
});


