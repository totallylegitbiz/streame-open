<?
require('bootstrap.php');


$post = Orm::site_post()->find();
$post->_index();


exit;
$action = Orm::user_action()->find();

$user = $action->user;

Orm::user_taste()->recalculate($user);
Orm::user_taste()->get_results($user,[], 10,0);

exit;

/*
foreach (Orm::site_post()->order_by('id', 'desc')->find_all() as $post) {
  Logger::debug("Working on #%o - %o", $post->id, $post->title);
  $post->extract_topics();
}
exit;
*/

$urls = array (
  "http://pando.com/",
  );
  
foreach($urls as $url) {
  try {
    $site = Orm::site()->create_by_url($url);
/*     $site->poll(); */
  } catch (Exception $e) {
    Logger::error("Exception %o", $e);
  }
}

exit;

/*
$post  = Orm::site_post()->order_by('id', 'desc')->find(); 
$post->extract_topics();
print_r(Sanitize::arrayitize($post->to_array()));
exit;
*/
  
foreach (Orm::site_post()->order_by('id', 'desc')->find_all() as $post) {
  Logger::debug("Working on #%o - %o", $post->id, $post->title);
  $post->extract_topics();
}
exit;
/* $post  = Orm::site_post()->find(); */


exit;
/*
foreach (Orm::site_post()->find_all() as $post) {
  $post->_pio_create();
}
*/

foreach (Orm::user()->find_all() as $user) {
  $user->_pio_create();
}


exit;
/*
$action = Orm::user_action()->find();
$action->propagate();

exit;
*/

$user  = Orm::user()->find();
$post  = Orm::site_post()->find();
$user->record_action($post,'like');
exit;
foreach (Orm::site()->randomize()->find_all() as $site) {
  $site->poll();
}

exit;
foreach (Orm::site_post()->randomize()->where('main_image_id', 'IS', null)->find_all() as $post) {
  $post->find_best_image();
/*   $post->extract_readability(); */
/*   print_r(Sanitize::arrayitize($post)); */
}

exit;
/*
$user  = Orm::user()->find();
foreach (Orm::site_post_entity()->where('wiki_entity', '=', 'Republican Party (United States)')->find_all() as $entity) {
  $post = $entity->post;
  $post->_pio_action($user, 'view');  
}

exit;
*/

$user  = Orm::user()->find();

foreach (Orm::site_post()->get_recommended($user,10,0) as $post) {
  echo $post->title . "\n";
}


exit;
foreach (Orm::site_post()->find_all() as $post) {
  $post->_pio_create();
}

exit;

use PredictionIO\PredictionIOClient;

$client = PredictionIOClient::factory(["appkey" => PREDICTIONIO_API_KEY,'apiurl'=>PREDICTIONIO_API_ENDPOINT]);

$command  = $client->getCommand('create_item', array('pio_iid' => 'bookId1', 'pio_itypes' => 1,'cat'=>1));
$response = $client->execute($command);

exit;


$urls = [
"bleacherreport.com",
"billmoyers.com",
"sci-news.com",
/* "eweek.com", */
"techdirt.com",
"thebarkpost.com",
"techcrunch.com",
"msnbc.com",
"infoq.com",
"arstechnica.com",
"dailykos.com",
"newscientist.com",
"washingtonpost.com",
"entrepreneur.com",
"politicususa.com",
"theguardian.com",
/* "citypaper.net", */
"dailymail.co.uk",
"salon.com",
"qz.com",
"http://money.cnn.com/",
'wired.com',
'http://laughingsquid.com/',
'http://www.wnyc.org/',
'theverge.com',
'http://news.yahoo.com/',
'http://www.foxnews.com/',
'http://www.theguardian.com/',
'http://www.nbcnews.com/',
'http://www.usatoday.com/',
'http://www.bbc.co.uk/news/',
'http://www.latimes.com/',
];

foreach($urls as $url) {
  try {
    $site = Orm::site()->create_by_url($url);
/*     $site->poll(); */
  } catch (Exception $e) {
    Logger::error("Exception %o", $e);
  }
}