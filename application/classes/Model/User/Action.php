<?

/*
CREATE TABLE `user_actions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `target_type` varchar(50),
  `target_id`  int(11) unsigned NOT NULL,
  `action`     varchar(50)  NOT NULL,
  `rating`  int unsigned default null,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
*/

class Model_User_Action extends Orm {
   
  protected $_belongs_to = [
   'user'      => ['model'=>'user','foreign_key'=>'user_id']
  ];
   
  function target() {
    return Orm($this->target_type, $this->target_id);
  }
  
  function propagate() {
    $target = $this->target();
    
    if (!$target instanceof Orm_Pio) {
      return false;
    }
    
    return $target->_pio_action($this->user, $this->action, $this->rating, $this->created);
    
  }
  
  function to_array($extra_fields = []) { 
    return [
      'id'          => (integer) $this->id, 
      'target_type' => $this->target_type,
      'target_id'   => $this->target_id,
      'action'      => $this->action
    ];
  }
}
