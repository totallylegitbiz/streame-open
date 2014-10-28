<?

class Minion_Task_Streame_Import_Reddit extends Minion_Task {

  protected function _execute(array $params) {
  
    $sites = [];
    
    $subreddits = [
      'worldnews', 
      'news',
      //'UpliftingNews', 
      'TrueNews', 
      'InDepthStories', 
      'Photoessay',
      //'Interview',
      //'Entertainment',
      //'TrueReddit',
      'NewsOfTheWeird',
      //'tech',
    ];
    
    $reddit     = new Reddit_Api();
    $cache      = new Cache_Remote();
    
/*     shuffle($subreddits); */
    
    foreach ($subreddits as $subreddit) {
    
      $after     = null;
      $found     = true;
      
/*       while($found) { */
        
        $listings  = $reddit->getListings($subreddit,1000, $after, 'hot');
        $after     = $listings['data']['after'];
        $found = false;
        
        foreach ($listings['data']['children'] as $listing) {
          
          $found = true;
          $url = 'http://' . $listing['data']['domain'];
          
          
          if (!isset($sites[$url])) {
            $sites[$url] = $listing['data']['score'];
          } else {  
            $sites[$url] += $listing['data']['score'];
          }
 
          
          
          $key = ['REDDIT_IMPORT', $url];
          
          if ($cache->get($key)) {
/*             Logger::debug('DOMAIN ALREADY TRIED: %o', $url); */
            continue;
          }
          
          $cache->set($key, 1, WEEK);
          Logger::debug("Url: %o", $url);
          
/*
          Task::queue(function() use ($url) {
            try {
              $site = Orm::site()->create_by_url($url);
            } catch (Exception $e) {
              Logger::error("Exception %o", $e);
            }
          });
*/
          
        }        

      }
    
/*     } */
    

    $useful = [];
    echo "\n\$urls = [\n";
    foreach ($sites as $site=>$i) {
      if ($i > 500) {
        $useful[] = $site;
        try {
          Orm::site()->create_by_url($site);
        } catch (Exception $e) {
          Logger::error("Exception %o", $e);
        }
        echo "'" . $site ."',\n";
      }
    }
    
    echo "];\n\n";
    
    
  }
    
}