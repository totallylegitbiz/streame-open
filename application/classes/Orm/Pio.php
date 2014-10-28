<?

use PredictionIO\PredictionIOClient;

Class Orm_Pio extends Orm_Es {
  
  var $_pio_client = null;
  function _init() {
    
    if ($this->_pio_client) {
      return;
    }
    
    $this->_pio_client =  PredictionIOClient::factory([
      'appkey' => PREDICTIONIO_API_KEY,
      'apiurl' => PREDICTIONIO_API_ENDPOINT
    ]);

    parent::_init();
    
  }
  
  function _pio_data() {
    throw new Exception('Please override _pio_data');
  }
  
  function _pio_id() {
    return $this->_object_name . ':' . $this->id;
  }
  
  function _pio_type() {
    return $this->_object_name;
  }
  
  function _pio_action(Model_User $user, $action, $rating = null, $ts = null) {
      
    $this->_init();
    
    if (!$ts) {
      $ts = time();
    }
    
    $this->_pio_client->identify($user->id);
    $request = ['pio_action' => $action, 'pio_iid' => $this->_pio_id()];
    
    if ($rating) {
      $request['pio_rate'] = $rating;
    }
    
    $command = $this->_pio_client->getCommand(
      'record_action_on_item', 
      $request
    );
    Logger::debug("PIO:record_action_on_item %o", $request);
    $r = $this->_pio_client->execute($command);
    
  }
  
  function _pio_create () {
  
    $this->_init();
    $pio_data = $this->_pio_data();    
    
    $pio_data['pio_iid']    = $this->_pio_id();
    $pio_data['pio_itypes'] = $this->_pio_type();
    Logger::debug("Creating Item: %o", $pio_data);
    $command  = $this->_pio_client->getCommand('create_item', $pio_data);
    $response = $this->_pio_client->execute($command);

  }
  
  function _pio_item_rec(Model_User $user, $engine, $limit = 10, $offset = 0) {
    $this->_init();
        
    $this->_pio_client->identify($user->id);
    
    $command = $this->_pio_client->getCommand(
      'itemrec_get_top_n',
      ['pio_engine' => $engine, 'pio_n' => $limit + $offset]
    );
    
    $recs = $this->_pio_client->execute($command);

    $ids = [];
    foreach ($recs['pio_iids'] as $rec) {
      if ($offset-- > 0) {
        continue;
      }
      list($model, $id) = explode(':',$rec);
      $ids[] = $id;
    }
   
    return $this->where_ids($ids)->find_all();

  }

}