<?

Interval::cron('25 */4 * * * *', 'cleanup_logs',  function(){
  DB::delete('logs')
    ->where('created', '<', new Database_Expression('DATE_SUB(now(),INTERVAL 3 DAY)'))
    ->execute();
});

Interval::cron('44 * * * * *', 'session_cleanup',  function(){
  Mango::factory('session')->clean();  
});
