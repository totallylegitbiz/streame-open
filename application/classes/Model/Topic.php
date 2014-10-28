<?

class Model_Topic extends Orm {
  
  var $_serialize_columns = ['meta'];
  
  function to_array($extra_fields = []) {    
    return [
      'id'     => $this->id,
      'topic'  => $this->topic,
      'wiki' => [
        'url'   => $this->wiki_url,
        'entity'=> $this->wiki_entity,
      ]
    ];
  }
  
  function attributes() {

    return [
      'topic_id' => $this->id
    ];
    
  }

}