<?

class Model_Site_Post_Topic extends Orm {

  protected $_belongs_to = [
    'post'        => ['model'=>'site_post','foreign_key'=>'post_id'],
    'topic'       => ['model'=>'topic',    'foreign_key'=>'topic_id']
  ];
  
  function to_array($extra_fields = []) {    
    return [
      'id'     => $this->id,
      'topic'  => $this->topic,
      'score'  => $this->score,
    ];
  }
}



