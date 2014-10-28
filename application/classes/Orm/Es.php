<?

Class Orm_Es extends Orm {
  
  var $_es_client  = null;
  var $_index_map  = [];
  var $_cache      = null;
  var $_index_ignore = [];
  
  function _path() {
    return 'http://' . ES_SERVER . '/models/' . $this->_object_name;
  }
  function _init() {
    
    if ($this->_es_client) {
      return;
    }
    
    return $this->_es_client = ElasticSearch\Client::connection($this->_path());
    
  }
  
  function update_index() {
    $this->_index();
  }
  
  function _index() {

    if (!$this->id) {
      throw new Exception('Unable to index unsaved model');
    }
    
    Logger::debug('Indexing %o/%o', $this->_path(), $this->id);
    
    $this->_init();
    
    $index_array  = Array();
    
    foreach ($this->_table_columns as $col=>$spec) {
      if ($this->$col instanceof DateTime) {
        $index_array[$col] = $this->$col->format('c');
      } else {
        $index_array[$col] = $this->$col;
      }
    }
        
    $index_array = $this->_index_extra($index_array);
    $index_array = Sanitize::arrayitize($index_array, false);

    foreach ($this->_index_ignore as $col) {
      unset($index_array[$col]);
    }
    
    $r = $this->_es_client->index($index_array, $this->id);
    
    if ((!isset($r['ok']) || $r['ok'] != 1) && !isset($r['_version'])) {  
      Logger::debug('Error updating index for  %o/%o: %o', $this->_object_name, $this->id, $r);
    }
  
  }
  
  function _delete() {
    
    Logger::debug('Deleting %o/%o', $this->_path(), $this->id);
    if (!$this->id) {
      throw new Exception('Unable to delete unsaved model');
    }
    
    $this->_init();
    $this->_es_client->delete($this->id);
  }
  
  function on_delete($id) {
    $this->_delete();
  }
  
  function search( $query, $limit = 100, $offset = 0 ) {
  
    $this->_init();
    
    if (is_array($query)) {
      $query['size']  = $limit;
      $query['from']  = $offset;
      $query['fields'] = Array('id');
      $r = $this->_es_client->search($query);
    } else {
      $r = $this->_es_client->search($query, Array('size'=>$limit,'from'=>$offset,'fields' => Array()));
    }
    
    
    if (isset($r['status']) && $r['status'] != 200) {
      if (isset($r['error'])) {
        throw new Except("ElasticSearch Exception: %o", $r['error']);
      }
    }
    
    //$results = Orm($this->_object_name,$hit['_id']));
    
    $result = new Orm_Search_Result();
    
    $ids = Array();
    foreach ($r['hits']['hits'] as $hit ) {
      $ids[] = $hit['_id'];  
    }
    
    if (count($ids)) {
      foreach(Orm($this->_object_name)->where_ids($ids)->find_all() as $obj) {
        $result->docs[] = $obj;
      }
    }
    
    $result->facets     = ifset($r['facets'],[]);
    $result->total_hits = $r['hits']['total'];
    
    return $result;
    
  }
  
  function _index_extra($index) {
    return $index;
  }
  
  function _map($delete = true) {
  
    $this->_init();
    
    $map = [
     // "_id" => ["index" => "not_analyzed", "store" => "yes"],
      "id"  => ["index" =>"not_analyzed", "store" => "yes",  "type" => "integer"]
    ];

    foreach ($this->_table_columns as $col=>$spec) {

      if (isset($spec['type']) && $spec['type'] == 'string') {
        $map[$col] = ['index' => 'not_analyzed', "type" => "string"];
      }
    }

    foreach ($this->_serialize_columns as $col) {
      $map[$col] = ['index' => 'not_analyzed', "type" => "array"];
    }
    
    foreach ($this->_index_ignore as $col) {
      unset($map[$col]);
    }
 
    $map = $this->_map_extra($map);
    
    if (!is_array($map)) {
      throw new Exception("Map must be an array");
    }
    
    //This will delete everything
    if ($delete) {
      $this->_es_client->request('_mapping', 'DELETE', [], true);
    }
    
    Logger::debug("Setting map: %o", [$this->_object_name => ['properties'=>$map]]);
    return $this->_es_client->request('_mapping', 'PUT', [$this->_object_name => ['properties'=>$map]], true);
      
  }
  
  function _map_extra($map) {
    return $map;
  }
  
}
