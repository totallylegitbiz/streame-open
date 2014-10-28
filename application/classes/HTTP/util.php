<?

Class Http_Util {

  static function basic_auth ($user, $pass, $realm) {
  
    if (
      ifset($_SERVER['PHP_AUTH_USER']) == $user
     &&
      ifset($_SERVER['PHP_AUTH_PW']) == $pass
    ) {
      return true;
    }
    
    header("WWW-Authenticate: Basic realm=\"${realm}\"");
    header('HTTP/1.0 401 Unauthorized');
    echo 'Access Denied';
    exit;
  }
  
  static function split_url($url) {

    $params = $uri = '';
  
    $url = preg_replace('/\/{2,}/', '/',$url);
  
  
    if (strpos($url, '/') !== 0)
      $url = '/' . $url;
  
    if ($i = strpos($url,'?')) {
      $uri    = UTF8::substr($url,0,$i);
      $params = UTF8::substr($url,$i+1);
    } else {
     $uri = $url;
  
    }
    //Remove trailing /
    if (strlen($uri) > 1 && $uri[strlen($uri)-1] == '/') {
      $uri = UTF8::substr($uri, 0,strlen($uri)-1);
    }
  
    return Array($uri, $params);
  }
  
  static function append_url_params($url, $params) {
  
    if (!count($params)) {
      return $url;
    }
  
    if (!is_array($params)) {
      parse_str($params, $params);
    }
  
    if (!count($params)) return $url;
  
    if (strpos($url, '?') !== false) {
      list($url, $url_params) = explode('?', $url);
      parse_str($url_params, $url_params);
      $params = array_merge($url_params, $params);
    }
  
    return $url . '?' . http_build_str($params);
      
  }
  
  static function get_ogs ($url) {
  
    $key = 'get_og::' . $url;
    
    $cache = new Cache_Remote();
    
    if ($r = $cache->get($key)) {
      return $r;
    }
    
    libxml_use_internal_errors(true);
    $c = file_get_contents($url);
    $d = new DomDocument();
    $d->loadHTML($c);
    $xp = new domxpath($d);
    
    $og_fields = Array('title', 'description', 'image','site_name','url');
    $ogs = Array();
    
    foreach ($og_fields as $field) {
      $ogs[$field] = null;
      foreach ($xp->query("//meta[@property='og:{$field}']") as $el) {
        $ogs[$field] = $el->getAttribute("content");
        break;
      }
    }
    
    $cache->set($key, $ogs, HOUR);

    return $ogs;
    
  }
  
  static function extract_imgs($url) {
        
    $dom = new DOMDocument;

    libxml_use_internal_errors(true);
    @$dom->loadHTMLFile($url);

    $src_images = Array();
    
    foreach ($dom->getElementsByTagName('img') as $img) {
      try {
        if ($img->hasAttribute('src')) {
          $src_images[] = self::resolve_rel($img->getAttributeNode('src')->value, $url);
        }
      } catch(Exception $e) {
        Logger::error('Error with element: %o', $e);
      }
    }

    return $src_images;

  }
  
  static function resolve_rel($rel, $base) {

    /* return if already absolute URL */
    if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;

    /* queries and anchors */
    if ($rel[0]=='#' || $rel[0]=='?') return $base.$rel;

    /* parse base URL and convert to local variables:
     $scheme, $host, $path */
    extract(parse_url($base));

    /* remove non-directory element from path */
    $path = preg_replace('#/[^/]*$#', '', $path);

    /* destroy path if relative url points to root */
    if ($rel[0] == '/') $path = '';

    /* dirty absolute URL */
    $abs = "$host$path/$rel";

    /* replace '//' or '/./' or '/foo/../' with '/' */
    $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
    for($n=1; $n>0; $abs=preg_replace($re, '/', $abs, -1, $n)) {}

    /* absolute URL is ready! */
    return $scheme.'://'.$abs;  
  }
  
}
