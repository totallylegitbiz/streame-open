<?



// Library includes
require CLASS_BASE . '/dwoo/dwooAutoload.php';

function Dwoo_Plugin_json (Dwoo $dwoo, $var) {
  return json_encode($var);
}

function Dwoo_Plugin_date_format (Dwoo $dwoo, $date, $format, $default = null) {

  if (is_null($date)) {
    return $default;
  }
  
  if (!$date instanceof Datetime) {
    $date = new Datetime($date);
  }
  
  return $date->format($format);
  
}

function Dwoo_Plugin_login_url (Dwoo $dwoo) {

  $path = Dwoo_Plugin_path ($dwoo, '/u/login', 'www');
  $from = Dwoo_Plugin_path ($dwoo, $_SERVER["REQUEST_URI"]);
    
  return $path . '?from=' . urlencode($from);
  
}

function Dwoo_Plugin_static_map (Dwoo $dwoo, $lat, $lng, $width, $height, $zoom = 13, $name = '') {
  //https://maps.google.com/maps?q=39.211374,-82.978277+(My+Point)&z=14&ll=39.211374,-82.978277
  
  $name = urlencode($name);
  return "
    <div class=\"static-map\" data-lat=\"{$lat}\" data-lng=\"{$lng}\" data-zoom=\"{$zoom}\"><a href=\"https://maps.google.com/maps?q={$lat},{$lng}+{$name}\" target=\"_new\"><img src=\"//maps.googleapis.com/maps/api/staticmap?center={$lat},{$lng}&markers={$lat},{$lng}&zoom={$zoom}&size={$width}x{$height}&key=" . GOOGLE_API_BROWSER_KEY ."&visual_refresh=true&sensor=false\"></a>
    </div>
  ";
   
}

function Dwoo_Plugin_static (Dwoo $dwoo, $path, $app = 'static') {
  
  $base = '';
  
  if (!$app) {
    $app = 'static';
  }
  
  if ($app == 'static') {  
    $base = '//' . STATIC_DOMAIN;
  } else {
    $base = '//' . $app . '.' . BASE_DOMAIN;
  }
  
  if (substr($path,0,1) !== '/') {
    $path = '/static/' . (IS_DEV?rand(10000,99999):REV) . '/' . $path;
  }
  
  return $base . $path;
}

function Dwoo_Plugin_img (Dwoo $dwoo, Model_Image $src, $size='x', $extra="") {

  if (!$src->loaded()) {
    return '';
  }
  
  $dims   = Image::calc_resize_by_code($size, $src->width, $src->height);
  $width  = $dims['width'];
  $height = $dims['height'];
  return "<img src='" . $src->src($size) . "' width='$width' height='$height' {$extra} style='width: {$width}px; height: {$height}px'>";
}

function Dwoo_Plugin_path (Dwoo $dwoo, $path, $app = null, $scheme = 'https') {

  if (!$app) {
    $app = APP_NAME;
  }
  
  if ($app == 'www') {
    $app = '';
  } else {
    $app .= '.';
  }
  
  return $scheme . '://' . $app  . BASE_DOMAIN . $path;
    
}

function Dwoo_Plugin_ellipsis(Dwoo $dwoo, $string, $length, $end='…')
{
  $string = (string) $string;
  if (strlen($string) > $length)
  {
    $length -=  strlen($end);  // $length =  $length – strlen($end);
    $string  = substr($string, 0, $length);
    $string .= $end;  //  $string =  $string . $end;
  }
  return $string;
}
