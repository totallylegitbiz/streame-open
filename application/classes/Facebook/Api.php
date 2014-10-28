<?

require 'facebook.php';

Class Facebook_Api extends Facebook {

  var $session = null;
  
  function __construct () {
  
    $config = [
      'appId'        => FACEBOOK_APP_ID,
      'secret'       => FACEBOOK_APP_SECRET,
      'fileUpload'   => false,
      'sharedSession'=> true
    ];
    $this->session = Session::instance();
    
    $this->setAppId($config['appId']);
    $this->setAppSecret($config['secret']);
    if (isset($config['fileUpload'])) {
      $this->setFileUploadSupport($config['fileUpload']);
    }
    if (isset($config['trustForwarded']) && $config['trustForwarded']) {
      $this->trustForwarded = true;
    }
    $state = $this->getPersistentData('state');
    if (!empty($state)) {
      $this->state = $state;
    }
    
    $this->initSharedSession();
    
  }
  
  protected function setPersistentData($key, $value) {
 
    if (!in_array($key, self::$kSupportedKeys)) {
      self::errorLog('Unsupported key passed to setPersistentData.');
      return;
    }

    return $this->session->set($key, $value);
  }

  protected function getPersistentData($key, $default = false) {
    if (!in_array($key, self::$kSupportedKeys)) {
      self::errorLog('Unsupported key passed to getPersistentData.');
      return $default;
    }

    
    return ($v = $this->session->get($key))? $v : $default;
  }

  protected function clearPersistentData($key) {
    if (!in_array($key, self::$kSupportedKeys)) {
      self::errorLog('Unsupported key passed to clearPersistentData.');
      return;
    }

    $this->session->set($key, null);
  }
  
}