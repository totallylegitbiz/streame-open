<?

$_POST    = Sanitize::encode_html($_POST);
$_GET     = Sanitize::encode_html($_GET);
$_REQUEST = Sanitize::encode_html($_REQUEST);

//Not sure why, but _GET[path] is always set, this deletes it.
$t_keys = array_keys($_REQUEST);
if (($first_key = array_shift($t_keys)) && $first_key[0] == '/') {
  unset($_GET[$first_key]);
  unset($_REQUEST[$first_key]);
}

//Populate post with the file data
foreach ($_FILES as $k=>$v) {
  $_POST[$k] = $v;
}

function ifset ( &$var, $default = null) {
  return !isset($var)?$default:$var;
}

Class Except_Public extends Except {}
Class Except_Skip   extends Except {}
Class Except_Filter extends Except {}
Class User_Except   extends Except {}

define('SECOND', 1);
define('MINUTE', 60);
define('HOUR',   MINUTE*60);
define('DAY',    HOUR*24);
define('WEEK',   DAY*7);
define('MONTH',  floor(DAY*30.4)); //Almost
define('YEAR',   floor(DAY*365.25)); //Almost

//Stupid helpers
define('MINUTE_2',  MINUTE*2);
define('MINUTE_3',  MINUTE*3);
define('MINUTE_4',  MINUTE*4);
define('MINUTE_5',  MINUTE*5);
define('MINUTE_10', MINUTE*10);
define('MINUTE_15', MINUTE*15);

define('HOUR_2',    HOUR*2);
define('HOUR_3',    HOUR*3);
define('HOUR_4',    HOUR*4);
define('HOUR_5',    HOUR*5);
define('HOUR_6',    HOUR*6);
define('HOUR_7',    HOUR*7);
define('HOUR_8',    HOUR*8);
define('HOUR_9',    HOUR*9);
define('HOUR_10',   HOUR*10);
define('HOUR_11',   HOUR*11);
define('HOUR_12',   HOUR*12);


define('REV', App::rev());
define('PUSHER_BASE_CHANNEL', Push::get_channel(''));

define('FILTER_VALIDATE_IN_SET',        1005);
define('FILTER_VALIDATE_INT_SET',       1006);
define('FILTER_VALIDATE_STR_NOT_EMPTY', 1007);
define('FILTER_VALIDATE_STR',           1008);
define('FILTER_VALIDATE_STR_SET',       1009);
define('FILTER_VALIDATE_CLOSURE',       1010);
define('FILTER_VALIDATE_DAYTIME',       1011);
define('FILTER_VALIDATE_MODEL_ID',      1012);
define('FILTER_VALIDATE_PHONE',         1013); 

function Orm($model = null, $id = null) { return Orm::factory($model, $id); }

if (!function_exists('getallheaders')) {
  function getallheaders() {
     foreach ($_SERVER as $name => $value) 
     {
         if (substr($name, 0, 5) == 'HTTP_') 
         {
             $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
         }
     } 
     return $headers;
  }
}

function hostname($fqdn = true) {

  $hostname = trim(file_get_contents('/etc/hostname'));

  if (!$fqdn) {
    list($hostname) = explode('.', $hostname);
  }

  return $hostname;

}

function http_build_url($url, $array) {
  return $url . '?' . http_build_str($array);
}


function http_build_str ( $array ) {

  $params = Array();

  foreach ($array as $i=>$v) {
    $params[] = urlencode($i) . '='. urlencode($v);
  }
  return implode('&', $params);

}

function get_states() {
  return [
	'AL'=>'Alabama', 
	'AK'=>'Alaska', 
	'AZ'=>'Arizona', 
	'AR'=>'Arkansas', 
	'CA'=>'California', 
	'CO'=>'Colorado', 
	'CT'=>'Connecticut', 
	'DE'=>'Delaware', 
	'DC'=>'District Of Columbia', 
	'FL'=>'Florida', 
	'GA'=>'Georgia', 
	'HI'=>'Hawaii', 
	'ID'=>'Idaho', 
	'IL'=>'Illinois', 
	'IN'=>'Indiana', 
	'IA'=>'Iowa', 
	'KS'=>'Kansas', 
	'KY'=>'Kentucky', 
	'LA'=>'Louisiana', 
	'ME'=>'Maine', 
	'MD'=>'Maryland', 
	'MA'=>'Massachusetts', 
	'MI'=>'Michigan', 
	'MN'=>'Minnesota', 
	'MS'=>'Mississippi', 
	'MO'=>'Missouri', 
	'MT'=>'Montana',
	'NE'=>'Nebraska',
	'NV'=>'Nevada',
	'NH'=>'New Hampshire',
	'NJ'=>'New Jersey',
	'NM'=>'New Mexico',
	'NY'=>'New York',
	'NC'=>'North Carolina',
	'ND'=>'North Dakota',
	'OH'=>'Ohio', 
	'OK'=>'Oklahoma', 
	'OR'=>'Oregon', 
	'PA'=>'Pennsylvania', 
	'RI'=>'Rhode Island', 
	'SC'=>'South Carolina', 
	'SD'=>'South Dakota',
	'TN'=>'Tennessee', 
	'TX'=>'Texas', 
	'UT'=>'Utah', 
	'VT'=>'Vermont', 
	'VA'=>'Virginia', 
	'WA'=>'Washington', 
	'WV'=>'West Virginia', 
	'WI'=>'Wisconsin', 
	'WY'=>'Wyoming'];

}

function mb_str_replace($needle, $replacement, $haystack) {
    $needle_len = mb_strlen($needle);
    $replacement_len = mb_strlen($replacement);
    $pos = mb_strpos($haystack, $needle);
    while ($pos !== false)
    {
        $haystack = mb_substr($haystack, 0, $pos) . $replacement
                . mb_substr($haystack, $pos + $needle_len);
        $pos = mb_strpos($haystack, $needle, $pos + $replacement_len);
    }
    return $haystack;
}

