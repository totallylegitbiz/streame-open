<?

class Model_Site_Post extends Orm_Pio {
  
  protected $_belongs_to = Array(
    'site'        => Array('model'=>'site','foreign_key'=>'site_id'),
    'main_image'  => Array('model'=>'image','foreign_key'=>'main_image_id'),
  );
  
  protected $_has_many = Array(
    'post_images'   => Array('model'=>'site_post_image','foreign_key'=>'site_post_id'),
    'images'        => Array('model'=>'image','through'=>'site_post_images'),
    'topics'        => Array('model'=>'site_post_topic','foreign_key'=>'post_id')
  );
    
  function create_from_item( Simplepie_Item $item, Model_Site $site, $force = false ) {
   
    #$url = Sanitize::redirect_url(Sanitize::decode_html($item->get_permalink()));
 
    $url = Sanitize::decode_html($item->get_permalink());
    $url = Sanitize::remove_utm($url);
     
    $lock = new Lock('CREATE_FROM_ITEM::' . $url);
    
    if (!$lock->lock()) {
     throw new Except("Unable to get lock to import: %o", $url);
    } 
    
    if (!$force) {
    
      $post = Orm('site_post')->
        where_open()->
          where('url','=',$url)->
          or_where('uid','=', $item->get_id())->
        where_close()->
        where('site_id','=', $site->id)->find();
        
      if ($post->loaded()) {
        $lock->unlock();
        throw new Except_Skip("Already imported: %o", $post->permalink);
      }
      
    }
    
    $post = new Model_Site_Post();
        
    $post->permalink   = $url;
/*     $post->url         = Sanitize::ensure_html_decoded($url); //Sometimes these differ */
    $post->title       = Sanitize::ensure_html_encoded_once($item->get_title());
    $post->published   = $item->get_date('c');
    $post->created     = time();
    $post->uid         = $item->get_id();

    //$categories = $item->get_categories();
    $post->blurb       = Sanitize::ensure_html_decoded($item->get_description());
    $post->body_orig   = $item->get_content();    
    $post->site_id     = $site->id;
    $post->slug        = Sanitize::tagify($post->title);
    
     //We need
    try { 
      $post->save();
      $post->find_best_image();
      $post->extract_topics();
    } catch (Exception $e) {
      Logger::error('Unable to extra assets from post_id %o: %o - %o', $post->id, $e, $post->permalink);
    }
      
    $lock->unlock();
    
    $post->reload();
    $post->update_index();
    return $post;
    
  }
  
  function find_best_image() {
  
    $this->body = $this->extract_assets();
    $this->extract_main_image();
    
    $this->extract_readability(); 
    
    if (!$this->main_image_id) {
      $this->extract_assets($this->body_readability, false);
      $this->extract_main_image();
    }
    
    if (!$this->main_image_id) {
      $this->find_better_images();
    }
    
    if ($this->main_image_id) {
      $this->save();
      return true;
    }
    
    return false;

  }
  function update_index() {
  
    if (
      $this->status != 'ACTIVE'
    ||
      $this->site->status != 'ACTIVE'
    ) {
      $this->_delete();
    } else {
      $this->_index();
    }
    
  }
  
  function extract_readability() {
  
    $url = $this->permalink;
    Logger::debug('Extracting readability for: %o', $url);
      
      
    try { 
      $this->body_page_html = file_get_contents($url);
              
      if (!strlen($this->body_page_html)) {
        throw new Exception("Unable to grab %o", $url);
      }
      
      $r       = new Readability(Http_Util::tidy($this->body_page_html));
      $content = $r->getContent();
      
      $this->body_readability = $content['content'];
  
    } catch (Exception $e) {
      Logger::error('Exception extracting readability: %o', $e);
    }
    
    $this->save();
    
    return;

  }
  
  function extract_assets($html = null, $delete_before = true) {
  
    if ($delete_before) {
      foreach ($this->post_images->find_all() as $post_image) {
        $post_image->delete();
      }
    }
      
    $dom = new DOMDocument;
    
    if (!$html) {
      $html = $this->body_orig;
    }
    
    $html = mb_convert_encoding($html, "UTF-8", "auto");
    
/*     $html = Sanitize::purify_html($html);     */
    
    $html = mb_str_replace('<body>','', $html);
    $html = mb_str_replace('</body>','', $html);
    $html = '<?xml version="1.0" encoding="UTF-8" standalone="no"?><html><body>' . $html . '</body></html>';
    
    $dom->loadHTML($html);
    
    //Stupid, but you have to iterate backwards.
    $el_images = $dom->getElementsByTagName('img');
    
    for ($i = $el_images->length; --$i >= 0; ) { 
     
      try {
  
        $img = $el_images->item($i);
        
        //Let's see if this image even have a source, if not, remove it and move on.
        if (!$img->hasAttribute('src')) {
          $img->parentNode->removeChild($img); 
          continue;
        }
        
        //Make an attempt to upload this
        $src = $img->getAttributeNode('src')->value;
        
        $src = Http_Util::resolve_rel($src, $this->url);
        
        $image = Orm('image')->upload_remote($src);
       
        //Okay, this is a tracking pixel or something, kill it.
        if ($image->height < 5 && $image->width < 5) {  
          //Logger::debug('Image too small, deleting: %o', $src);
          $img->parentNode->removeChild($img); 
          continue;
        }
        
        $img->setAttributeNode(new DOMAttr('src',      $image->src('x'))); 
        $img->setAttributeNode(new DOMAttr('image-id', $image->id)); 
        
        $width_attr  = $img->hasAttribute('width');
        
        $orig_height = $orig_width = 0;
        
        if ($img->hasAttribute('height')) {
          $orig_height = $img->getAttributeNode('height')->value;
        }
        
        if ($img->hasAttribute('width')) {
          $orig_width = $img->getAttributeNode('width')->value;
        }
        
        //Override the height/width if they are not set.
        if (!$orig_height || !$orig_width) {
         $img->setAttributeNode(new DOMAttr('height', $image->height)); 
         $img->setAttributeNode(new DOMAttr('width',  $image->width)); 
        }
        
        
        $post_image                = Orm('site_post_image')
                                       ->where('site_post_id', '=', $this->id)
                                       ->where('image_id',     '=', $image->id)
                                       ->find();
                                  
        $post_image->site_post_id  = $this->id;
        $post_image->site_id       = $this->site->id;
        $post_image->image_id      = $image->id;
        $post_image->display_order  = $i;
        $post_image->src           = $src;
        $post_image->save();
       
      } catch(Exception $e) {
       Logger::debug('Error trying to upload image, removing: %s: %o', $src, $e);
       $img->parentNode->removeChild($img); 
      }
      
    }
    
    foreach ($dom->getElementsByTagName('body') as $body) { 
      $html = $dom->saveHTML($body);
      $html = mb_str_replace('<body>','', $html);
      $html = mb_str_replace('</body>','', $html);
      return $html;
    } 
    
    throw new Exception('Error extracting assets from post %o, no body tag found in output', $this->id);
    
  }
  
 
  function find_better_images() {
  
    $url         = Sanitize::redirect_url($this->permalink);
    
    $threshold   = 0.4;
    $tmp_files   = Array();
    $site_images = Array();
    $min_size_w  = 250;
    $min_size_h  = 50;
    
    Logger::debug('Finding better image for post_id: %o - %o', $this->id, $url);
    
    if (!$this->post_images->count_all()) {
      Logger::debug('No images found to make better of.');
      return;
    }
    $cache = new Cache_Remote();
    
    foreach (Http_Util::extract_imgs($url) as $source_url) {
      try {
        
        $key = Array(__CLASS__,__METHOD__, $source_url, 'FAILURE');
        
        if ($cache->get($key) == 1) {
          //This has been marked previously as a failure;
          continue;
        }
      
        $tmp_file = tempnam('/tmp','imag');
        
        if (!@copy($source_url, $tmp_file)) {
          throw new Except('Unable to copy image: %o', $source_url);
        }
        
        $image = new Image($tmp_file);
        
        if ($image->width < $min_size_w || $image->height < $min_size_h) {
          throw new Except('Image too small: %o', $source_url);
        }
        
        $site_images[$source_url] = $image;
        $tmp_files[]   = $image;
        
      } catch (Exception $e) {
        //Logger::debug("Error grabbing originals: %o", $e);
        $cache->set($key, 1, 7 * DAY);
      }
    }

    if (!count($site_images)) {
      Logger::debug('No images found on %o', $url);
      return;
    }
    
    foreach ($this->post_images->find_all() as $post_image) { 
      try {
        
        Logger::debug('Trying: %o', $post_image->image->src('x'));
        
        $target_image        = $post_image->image->original_image();
        //$tmp_files[]         = $target_image->original_file();
        
        $target_puzzle       = $target_image->puzzle_cvec();
        
        $similarities = Array();
        
        foreach ($site_images as $source_url => $site_image) {
        
          if ($site_image->width * $site_image->height < $site_image->width * $site_image->height) {
            continue;
          }
          
          $resize_image = $site_image->resize($target_image->width, $target_image->height, Image::T_CROP);
          
          $tmp_file = tempnam('/tmp','image') . '.png';
          $tmp_files[] = $tmp_file;
          $resize_image->save($tmp_file, Image::PNG);
          
          $resize_puzzle = puzzle_fill_cvec_from_file($tmp_file);
          
          $d = puzzle_vector_normalized_distance($target_puzzle, $resize_puzzle);
          
          //Logger::debug("Diff %o for %o: %o", $d, $source_url, $tmp_file);
          
          if ($d > $threshold) {
            continue;
          }
          
          $similarities[floor($d * 100000)] = $source_url;
        }
      
        if (!count($similarities)) {
          continue;
        }
        
        ksort($similarities);
        
        $source_url   = array_shift($similarities);
        $source_image = Orm('image')->upload_remote($source_url);
        
        if ($source_image->id == $post_image->image_id) {
           Logger::debug("Same image: %o", $source_url);
           continue;//Duh, it's the same one
        }

        $post_image->image_id = $source_image->id;
        $post_image->save();
        
        Logger::debug("Found a bigger image: %o", $source_url);
        
      } catch (Exception $e) {
        Logger::debug("Error linking: %o: %o", $post_image->image->src('x'), $e);
      }
    }
    
    foreach ($tmp_files as $tmp_file) {
      @unlink($tmp_file);
    }
    
  }
  
  function get_recommended(Model_User $user, $limit = 10, $offset = 0) { 
      return Orm::user_taste()->get_results($user,[], $limit,$offset);
      
/*       $posts = $this->limit($limit)->offset($offset)->order_by('published', 'desc')->where('main_image_id', 'IS NOT', null)->find_all(); */
      
    /*
  
    try {
      $posts = $this->_pio_item_rec($user, 'younolike', $limit, $offset);
    } catch (Exception $e) {
      //Something happened, let's try the old way.
      Logger::debug('EXCEPT: %o', $e);
      Logger::debug("OFFSET %o / LIMIT %o", $limit, $offset);
      $posts = $this->limit($limit)->offset($offset)->order_by('published', 'desc')->where('main_image_id', 'IS NOT', null)->find_all();
    }
    
*/
    return $posts;
  }
  
  function extract_topics() {
    
    $r = Client_Yahoo::content_analysis(Sanitize::decode_html(strip_tags(str_ireplace('readability','', $this->title . "\n" . $this->body_readability . "\n". $this->body_orig))));

    if (!$r) {  
      Logger::error("Yahoo context api gave us nothing for id #%o", $this->id);
      return;
    } 
    
    foreach ($this->topics->find_all() as $topic) {
      $topic->delete();
    }
    
    if (Sanitize::is_assoc($r)) {
      $r = Array($r); //Yeah, dumb, but Yahoo! is tricky.
    }
    
    foreach ($r as $topic_array) {
      
      $wiki_entity = $wiki_url = null;

      if (isset($topic_array['wiki_url'])) {
        $wiki_url   = $topic_array['wiki_url'];
        if (preg_match('/\/([^\/]+)$/', $wiki_url, $matches)) {
          $wiki_entity = Sanitize::encode_html(urldecode(str_replace('_', ' ', $matches[1])));  
        }
      }
      
      if ($wiki_entity) {
        $topic             = Orm('topic')
                               ->where('wiki_entity','=', $wiki_entity)
                               ->find();
      } else {
        $topic             = Orm('topic')
                               ->where('topic','=', $topic_array['text']['content'])
                               ->where('wiki_entity','IS', null)
                               ->find();
      }
      
      
      if (!$topic->loaded()) {
        //Can't find the topic, let's just save it.  
        $topic->topic       = $topic_array['text']['content'];
        $topic->wiki_entity = $wiki_entity;
        $topic->wiki_url    = $wiki_url;
        $topic->meta        = $topic_array;
        $topic->save();
        $topic->reload();
      }
      
      if ($this->topics->where('topic_id','=', $topic->id)->find()->loaded()) {
        //We already have this one.
        continue;
      }
      
      $post_topic = Orm('site_post_topic');
      $post_topic->score      = $topic_array['score'];
      $post_topic->topic_id   = $topic->id;
      $post_topic->post_id    = $this->id;
      $post_topic->site_id    = $this->site_id;
      $post_topic->start_char = $topic_array['text']['startchar'];      
      $post_topic->save();

    }
  
  }
  
  function extract_main_image( $save = false ) {
    
    foreach ($this->post_images->order_by('display_order')->find_all() as $post_image) {
      if ($post_image->image->height >= 10 && $post_image->image->width >= 250) {
        $this->main_image_id = $post_image->image->id;
        if ($save) {
          $this->save();
        }
        return $post_image->image;
      }
    }
    
    $this->main_image_id = null;
    
  }
  
  function update_score() {
    
    $post_shares = Orm('score')->get_scores($this);
    $site_shares = Orm('score')->get_scores($this->site);
    
    if (is_null($this->published) || $this->published > $this->created) {
      $score_base = $this->created->getTimeStamp();
    } else {
      $score_base = $this->published->getTimeStamp();
    }
  
    $score_max_boost = 12 * HOUR;
    
    $i          = 0;
    $post_score = 0;
    $site_score = 0;
    
    foreach ($post_shares as $network => $network_score) {
      $post_score += $network_score['percentile'];
      $i++;
    }
    
    $post_score = $i?$post_score /= $i:0;
    
    $i = 0;
  
    foreach ($site_shares as $network => $network_score) {
      $site_score += $network_score['percentile'];
      $i++;
    }
    
    $site_score = $i?$site_score /= $i:0;
    $site_score *= .5; //Half boost for the site
    
    $score = $site_score > $post_score? $site_score:$post_score;
      
    Logger::debug("Updating score for post %o to %o, site: %o, post: %o", $this->id, $score, $site_score, $post_score);
    $this->score            = (integer) ($score_base + ($score_max_boost * $score));
    $this->score_multiplier = $score;
    
    $this->save();
    
  }
  
  function _map_extra($map) {
    $map['topics'] = Array(
      'dynamic'    => true,
      'properties' => Array(
        'topic' => Array(
          'type'   => 'multi_field',
          'index'  => 'analyzed',
          'fields' => Array(
            'topic'     =>  Array( 'type'=> 'string', 'index'    => 'not_analyzed' ),
            'lowercase' =>  Array( 'type'=> 'string', 'analyzer' => 'lowercase_analyzer' ),
            'suggest'   =>  Array( 'type'=> 'string', 'analyzer' => 'suggest_analyzer' )
          )
        ),
        'wiki' => Array(
          'type'   => 'multi_field',
          'index'  => 'analyzed',
          'fields' => Array(
            'wiki'      =>  Array( 'type'=> 'string', 'index'    => 'not_analyzed' ),
            'lowercase' =>  Array( 'type'=> 'string', 'analyzer' => 'lowercase_analyzer' ),
            'suggest'   =>  Array( 'type'=> 'string', 'analyzer' => 'suggest_analyzer' )
          )
        )
      )
    );
    return $map;
  }
  
  function _index_extra($index) {
  
    $index['body_text'] = Sanitize::decode_html(strip_tags($this->body_orig));
    
    $topic_array = Array();
    
    foreach ($this->topics->find_all() as $topic) { 
      $topic_array[] = [
        'score' => (float)   $topic->score, 
        'topic' => (string)  $topic->topic->topic, 
        'wiki'  => (string)  $topic->topic->wiki_entity, 
        'id'    => (integer) $topic->topic->id,
      ];
    }
     
    $index['topics'] =  $topic_array;
    
    $categories = Array();
    
    foreach ($this->site->categories->find_all() as $category) {
      $categories[] = $category->id;
    }
    
    $index['site_category_ids'] = $categories;
    $index['site_primary_category_id'] = null;
    
    if ($site_primary_category = $this->site->primary_category()) {
      $index['site_primary_category_id'] = $site_primary_category->id;
    }
    
    return $index;
  }
  
  function path($full = false) {
    if ($full) {
      return 'http://' . BASE_DOMAIN . '/p/' . $this->id .'/'. $this->slug;
    }
    return '/p/' . $this->id .'/'. $this->slug;
  }
  

/*
  
  function _pio_data() {
  
    $topics = $wiki_entitys = [];
    foreach ($this->topics->find_all() as $topic) {
      $topics[] = $topic->topic;
      if ($topic->wiki_entity) {
        $wiki_entitys[] = $topic->wiki_entity;
      }
    }
    
    $data = [
      'topics'         => $topics,
      'wiki_entitys'   => $wiki_entitys,
      'site_id'        => $this->site->id,
      'pio_startT'     => $this->published->getTimeStamp()
    ];
    
    return $data;
  }
*/
  
  function attributes() {

    $topic_ids = [];
    
    foreach ($this->topics->find_all() as $topic) { 
/*       if ($topic->topic->wiki_entity) { */
        $topic_ids[] = [$topic->topic->id, $topic->score];
/*       } */
    }
    
    return [
      'topic_id' => $topic_ids,
      'site_id'  => [[$this->site_id, .3]] // Let's say 30% of the article is the site.
    ];
    
  }
  
  function to_array($extra_fields = Array()) {
  
    
    if (!strlen($this->slug)) {
      $this->slug = Sanitize::tagify($this->title);
    }
        
    return [
          'id'         => (integer) $this->id,
          'main_image' => $this->main_image,
          'title'      => $this->title,
          'published'  => $this->published?$this->published->getTimeStamp():null,
          'ago'        => $this->published->ago(),
          'created'    => $this->created?$this->created->getTimeStamp():null,
          'score'      => (integer) $this->score,
          'score_multiplier' => (float) $this->score_multiplier,
          'url'        => $this->permalink,
          'path'       => $this->path(),
          'status'     => $this->status,
          'body'       => mb_substr(str_replace(array( "\n", "\t", "\r"), ' ', Sanitize::decode_html(strip_tags($this->body_orig))),0, 255),
          'topics'     => $this->topics->order_by('score', 'DESC')->find_all(),
          'site'       => $this->site,
/*           'score'          => (integer) $this->score, */
/*           'network_scores' => Orm('score')->get_scores($this), */
          'categories'     => $this->site->categories->find_all()
        ];
  }
  
  
  
}
