<?

class Model_Site extends Orm_Es {
  
  protected $_belongs_to = Array(
    'avatar'      => Array('model'=>'image','foreign_key'=>'avatar_image_id')
  );
  
  protected $_has_many = Array(
    'posts'           => Array('model'=>'site_post','foreign_key'=>'site_id'),
    'categories'      => Array('model'=>'category', 'foreign_key'=>'site_id', 'through' =>'site_categories'),
    'site_categories' => Array('model'=>'site_category', 'foreign_key'=>'site_id'),
  );
  
  function log($type, $body){
    Logger::debug("[%o] - %o", $type, $body);
  }
  
  function on_update($changed) {
    $this->update_index();
  }
  
  function create_by_url($url, $force = false) {
    $url_orig = $url;
    $url   = Sanitize::decode_html($url);
    $url   = Sanitize::redirect_url($url);
    
    $site    = Orm('site')->where('url','=', $url)->find();
    if ($site->loaded()) {
      return $site;
    }
    
    if (!$feed = $this->find_feed($url)) {
      throw new User_Except('Unable to parse feed for %s', $url);      
    }
  
    $url     = Sanitize::redirect_url($feed->get_link()); 
    $rss_url = Sanitize::redirect_url($feed->subscribe_url());
    $site    = Orm('site')
                ->where('rss_url','=', $rss_url)
                ->or_where('url','=', $url)
                ->find();
    
    if ($site->loaded()) {
      return $site;
    }
    
    $site    = new Model_Site();
    
    try {
      if ($avatar_url = $feed->get_image_url()) {
        $avatar = Orm('image')->upload_remote($avatar_url);
        $site->avatar_image_id = $avatar->id;
      }
    } catch (Exception $e) {
      Logger::error('Exception uploading image %o : %o', $avatar_url, $e);
    }
        
    $site->slug           = Sanitize::tagify($feed->get_title());
    $site->name           = $feed->get_title();
    $site->url            = $url;
    $site->rss_url        = $rss_url;
    $site->poll_enabled   = true;
    $site->status         = 'ACTIVE';
    $site->post_count     = 0;
    $site->is_subdomain   = Sanitize::has_subdomain($url);
    $site->created        = new DayTime();
    $site->poll_freq_secs = $this->calc_poll_freq();
    $site->save();
    $site->log("INFO", "Created from:" . $url_orig);
    
    Task::queue(function($site) {
      $site->poll();
      $site->reindex();
    }, $site, Task::PRIORITY_HIGH);

    return $site;
    
  }  
  
  function update_index() {
  
    if (
      $this->status != 'ACTIVE'
    ) {
      $this->_delete();
    } else {
      $this->_index();
    }
    
  }
  
  function find_feed($url, $discover = true ) {
  
    $url = Sanitize::redirect_url($url);
      
    if (empty($url)) {
      return null;
    }
    
    $feed = new SimplePie();
    $feed->set_cache_location('/tmp/');
    $feed->enable_cache(false);
    $feed->set_feed_url($url);
    $feed->set_autodiscovery_level(SIMPLEPIE_LOCATOR_ALL);
    $feed->set_max_checked_feeds(50);
    
    $r = @$feed->init();
    
    
    if (!$r) {
      
      if (!$discover) {
        return;
      }
      $feed_url = $url;
      
      if (!preg_match('/\/$/',$feed_url)) {
        $feed_url .= '/';
      }
      
      $feed_url .= 'feed/';
      
      if ($r = $this->find_feed($feed_url, false)) {
        return $r;
      }
      
      $feed_url = $url;
       
      if (!preg_match('/\/$/',$feed_url)) {
        $feed_url .= '/';
      }
      
      $feed_url .= 'rss';
        
      if ($r = $this->find_feed($feed_url, false)) {
        return $r;
      }
      
      if (!Sanitize::has_subdomain($feed_url) && !preg_match('/www\./', $feed_url)) {
        //Dude, let's prepend www.
        $feed_url = preg_replace('/^(http[s]?:\/\/)(.*)$/', '$1www.$2', $feed_url);
        return $this->find_feed($feed_url, false);
      }
      
      return null;
    }
    
    return $feed;
    
  }
  
  
  function get_feed() {
    $feed = new SimplePie();
    $feed->set_cache_location('/tmp/');
    $feed->enable_cache(false);
    $feed->set_feed_url($this->rss_url);
    $feed->init();
    return $feed;
  }
    
  function poll($force = false) {
    $start_ts = time();
    
    if (!$this->poll_enabled) {
      $this->log("WARNING", "Polling attempted but disabled");
      throw new Except_Skip("Polling disabled for #" . $this->id);
    }
    
    $poll_lock = new Lock(Array(__CLASS__, __METHOD__, $this->id), 15*MINUTE);
    
    if (!$force && !$poll_lock->lock()) {
      $this->log("WARNING", "Polling attempted but unable to get lock");
      throw new Except_Skip("Unable to get lock for #" . $this->id);
    }
    
    Logger::debug("Starting poll of: %o - %o", $this->id, $this->name);
    
    $this->log("INFO", "Beginning polling, last_poll:" . ($this->last_poll?$this->last_poll->ago():'Never'));
    
    $this->last_poll  = new Daytime();
    $this->save();
    
    $feed = $this->get_feed();
    foreach($feed->get_items() as $item) {
      
      $lock = new Lock('create_from_item:::' . $item->get_id());
    
      if (!$lock->lock()) {
        Logger::debug('Error grabbing lock to import: %o', $item->get_id());
        $this->log("WARNING", "Error grabbing lock to import:" . $item->get_id());
        continue;
      }
      
      try {
        $post = Orm('site_post')->create_from_item($item, $this);      
      } catch (Except_Skip $e) {
        
      } catch (Exception $e) {
        Logger::error('Error importing %o: %o', $item->get_permalink(), $e);
        $this->log("ERROR", "Error importing: " . $item->get_permalink() . ' - ' . $e->getMessage());
      }
      
      $lock->unlock();
      
    }
    
    $this->last_poll  = new Daytime();
    $this->post_count = $this->posts->count_all();
    $this->save();
    
    $poll_lock->unlock();
    
    Logger::debug("Done poll of: %o - %o", $this->id, $this->name);
    $this->log("INFO", "Done polling in: " . (time()-$start_ts) . ' seconds');
    
    Event::add('site_poll_time',  Array('site_id' => $this->id), (time()-$start_ts));
    Event::add('site_post_count', Array('site_id' => $this->id), $this->post_count);
    
  }
  
  function add_category(Model_Category $category, $is_primary = false) {
  
    $site_category = Orm('site_category')->where('site_id','=',$this->id)->where('category_id','=', $category->id)->find();
    
    if ($site_category->loaded()) {
      
      if ($is_primary != $site_category->is_primary) {
        $site_category->is_primary = $is_primary;
        $site_category->save();  
      }
      
      return $site_category;
    } 
    
    $site_category              = new Model_Site_Category();
    $site_category->site_id     = $this->id;
    $site_category->category_id = $category->id;
    $site_category->is_primary  = $is_primary;
    
    $site_category->save(); 
  
    return $site_category;
    
  }
  
  function primary_category() {
    
    $site_category = Orm('site_category')->where('site_id','=',$this->id)->where('is_primary','=', true)->find();
    
    if ($site_category->loaded()) {
      return $site_category->category;
    }
    
    return null;
    
  }
  
  function reindex() {
  
    $this->log("INFO", "Reindexing");
    
    foreach ($this->posts->find_all() as $post) {
      $post->update_index();
    }
    $this->update_index();
    
    
  }
  
  function remove_category(Model_Category $category) {
  
    $site_category = Orm('site_category')->where('site_id','=',$this->id)->where('category_id','=', $category->id)->find();
    
    if ($site_category->loaded()) {
      $site_category->delete();  
    }
    
  }
  
  function calc_poll_freq($min = MINUTE_10, $max = HOUR_3) {  
    return round(($max - $min) * (1 - $this->daily_freq_percentile)) + $min;   
  }
  
  function update_freq() {
  
    if (!$this->loaded()) {
      throw new Exception('Must be loaded to update freq');  
    }
    
    $check_days = 7;
  
    $freq_query = "
      SELECT count(*) / $check_days as daily_freq 
      FROM site_posts 
      WHERE published >= DATE_SUB(now(),INTERVAL $check_days DAY)
      AND site_id = {$this->id}";
    

    $r = $this->_db->query(Database::SELECT,$freq_query)->as_array();
    
    $this->daily_freq = $r[0]['daily_freq'];

    $percentile_query = "SELECT 
                      if (count(id),sum(if(daily_freq < {$this->daily_freq},1,0)) / count(id),0) percentile
                    FROM sites 
                      WHERE 
                        id !='{$this->id}'";
    
   
    $r = $this->_db->query(Database::SELECT,$percentile_query)->as_array();
        
    $this->daily_freq_percentile = $r[0]['percentile'];
    $this->poll_freq_secs        = $this->calc_poll_freq();
    
    $this->save();  
  }
  
  
  function _index_extra($index) {
  
/*     $index['network_scores'] =  Orm('score')->get_scores($this); */
    $index['nice_url']       =  $this->nice_url();
    
    $categories = Array();
    foreach ($this->categories->find_all() as $category) {
      $categories[] = $category->id;
    }
    
    $index['category_ids']        = $categories;
    $index['primary_category_id'] = null;
    
    if ($site_primary_category = $this->primary_category()) {
      $index['site_primary_category_id'] = $site_primary_category->id;
    }
    
    return $index;
  }
  
  function nice_url() {
  
    if (preg_match('/^http[s]?:\/\/(www\.)?([^\/]+)/i', $this->url, $matches)) {

      $domain = $matches[2];
      $path   = $matches[3];
      
      if ($path == '/') {
        $path = '';
      }
      $nice_url = $domain . $path;
      
      return $nice_url;
      
    }
    
    return null;
    
  }
  
  function site_polling() {
    
    Logger::debug("Starting polling");
  
    $sites = Orm('site')
              ->where('status','=', 'ACTIVE')
              ->where('poll_enabled','=', true)
              ->where('last_poll','<', new Database_Expression('DATE_SUB(now(),INTERVAL poll_freq_secs SECOND)'))
              ->or_where('last_poll', 'IS', null)
              ->order_by('last_poll', 'DESC')
              ->find_all();
              
    foreach ($sites as $site) {
    
      //Just to make sure it doesn't get picked up in the next minute run
      $site->last_poll = new DayTime();
      $site->save();
      
      Task::queue(function($site) {
        Logger::debug('Polling site: %o', $site->id);
        try {
          $site->poll();
        } catch (Except_Skip $e) {
        } catch (Exception $e) {
          $site->log('ERROR', 'Error polling:' . $e->getMessage());
          Logger::error("Unable to poll site %o: %o", $site->id, $e->getMessage());
        }
        Logger::debug('Done site: %o', $site->id);
      }, Array($site), Task::PRIORITY_NORMAL);
    }

    
    
  }
  function to_array($extra_fields = Array()) {
   
    return Array(
      'id'             => (integer) $this->id,
      'name'           => $this->name,
      'path'           => '/s/' . $this->id .'/'. $this->slug,
      'rss_url'        => $this->rss_url,
      'url'            => $this->url,
      'nice_url'       => $this->nice_url(),
      'post_count'     => (integer) $this->post_count,
      'last_poll'      => $this->last_poll,
/*       'network_scores' => Orm('score')->get_scores($this) */
     );
            
  }

}
