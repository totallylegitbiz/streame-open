<?

Class Daytime extends DateTime {

  protected $ago_strings = Array(
    YEAR      => array('1 year ago',   '%d years ago'),
    MONTH     => array('1 month ago',  '%d months ago'),
    DAY       => array('1 day ago',    '%d days ago'),
    HOUR      => array('1 hour ago',   '%d hours ago'),
    MINUTE    => array('1 minute ago', '%d minutes ago'),
    SECOND    => array('now',          '%d seconds ago'),
  );
  
  static function factory($date = 'now') {
    return new Daytime($date);
  }
  
  public function toISO8601( $include_tz = true) {
  
    if ($include_tz) {
      return gmdate('c', $this->getTimestamp()). 'Z';
    }
    
    return gmdate('o-m-d\TH:i:s', $this->getTimestamp()); //2009-11-15T13:12:00  
  }
  
  public function __toString() {
    return (string) $this->getTimestamp();
  }
  
  public function to_day_array() {
    return [
      'month'=> gmdate('m', $this->getTimestamp()),
      'day'  => gmdate('d', $this->getTimestamp()),
      'year' => gmdate('o', $this->getTimestamp())
    ];
  }
  public function to_array() {
    return $this->__toString();
  }
  
  public function ago() {
  
    $diff      = time() - $this->getTimestamp();
    $outstring = '';
    
    foreach ($this->ago_strings as $ts => $lables) {
      if ($diff >= $ts) {
        $multiplier = floor($diff / $ts);
        if ($multiplier == 1) {
          $outstring = $lables[0];   
        } else {
          $outstring = sprintf($lables[1], $multiplier);
        }
        break;
      }
    }
    
    return $outstring;
    
  }

  public function format ( $format = 'l jS \of F Y H:i:s T', $timezone = null) {
       
    if ($timezone !== null) {
      $oldtz = $this->getTimezone();
      $tz = new DateTimeZone($timezone);
      $this->setTimezone($tz);
    }
        
    $r = parent::format($format);    
    
    if ($timezone !== null) {
      $this->setTimezone($oldtz);
    }
    
    return $r;
    
  }

}