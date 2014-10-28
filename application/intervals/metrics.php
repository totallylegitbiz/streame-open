<?

Interval::cron('*/5 * * * * *', 'metrics_gearman_queue',  function(){
  $r = DB::select(new Database_Expression('count(*) queue_length'))->from('gearman.gearman_queue')->as_assoc()->execute();
  Newrelic::metric('worker_queue_length', $r[0]['queue_length'] * 1000);
  Event::add ( 'queue_length', Array(), $r[0]['queue_length']);
});