<?

Interval::cron('0 1 * * * *', 'site_update_freq', function(){
  foreach (Orm('site')->find_all() as $site) {
    $site->update_freq();
  }
});

//Sets the site score at night.

/*
Interval::cron('0 3 * * * *', 'site_score_polling', function(){
  foreach (Orm('site')->randomize()->where('status','=', 'ACTIVE')->find_all() as $site) {
   try {
     Orm('score')->poll($site);
   } catch (Exception $e) {
     $site->log('ERROR', 'Error setting score:' . $e->getMessage());
     Logger::error("Exception: %o", $e);
   }
  }
});
*/

Interval::cron('* * * * * *', 'site_post_polling', function(){ 
  Orm::site()->site_polling();
});