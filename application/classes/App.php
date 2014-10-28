<?

Class App {
  static function rev ($force_check = false) {
  
    static $git_rev;
  
    $len = 6;
  
    $key = 'git_rev::'.$len;
    
    if (!$force_check) {
      if ($git_rev) return $git_rev;
      if ($git_rev = Cache_Local::get($key)) return $git_rev;
    }
  
    $git_base = APP_BASE.'/.git';
  
    $head     = file_get_contents($git_base . '/HEAD');
  
    if (strpos($head, ':') !== false) {
  
      foreach( explode("\n", $head) as $line )  {
  
        list($ref, $ref_path) = explode(': ', $line);
  
        if ($ref == 'ref') {
          break;
        }
  
      }
  
      $curr_head = $git_base . '/' . $ref_path;
  
      if (!file_exists($curr_head)) {
        return;
      }
  
      $git_rev = @file_get_contents($curr_head);
  
    } else {
      $git_rev = strim($head);
    }
  
    $git_rev = UTF8::substr($git_rev, 0, $len);
  
    // production infinite cache time
    $cache_time = 0;
    
    if (IS_DEV) {
      // development 5 second cache time
      $cache_time = 5;
    }
  
    Cache_Local::set($key, $git_rev, $cache_time);
  
    return $git_rev;
  
  }
}