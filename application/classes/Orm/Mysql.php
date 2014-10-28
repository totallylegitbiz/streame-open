<?

Class Orm_Mysql extends Kohana_ORM {

  //public function validate() { return Array(); }
   //public function load_filter($data) {return $data;}

  public function where_ids ($ids) {
      
    if (!sizeof($ids)) {
      return $this->where('id', 'IN', $ids); //Yes, this will return nothing
    }
    
    return $this
      ->where('id', 'IN', $ids)
      ->order_by(new Database_Expression('field('.$this->_primary_key.',' . implode(',',$ids).')'))
      ->limit(sizeof($ids));
  }
  
  public function randomize() {
    return $this->order_by(new Database_Expression('rand()'));   
  }
    
  static function _db_date( $date ) {
    
    if ($date instanceof Datetime) {
      $date = $date->getTimestamp();
    }
    
    if (is_null($date)) {
      return null;
    } else if (!is_numeric($date)) {
      $date = strtotime($date . " UTC");
    }

    return gmdate( 'Y-m-d H:i:s', $date);
  }
  
  public function save_reload (Validation $validation = NULL) {
    $this->save($validation);
    $this->reload();  
  }
  
  public function delete() {
    $this->before_delete();
    $r = parent::delete();
    $this->on_delete($this->id);
    return $r;
  }
  
  public function save (Validation $validation = NULL) {
     
    $changed  = $this->_changed;
    $is_new   = !$this->loaded();
    
    if ($is_new) {
      $this->before_insert($changed);
    } else {
      $this->before_update($changed);
    }
    
    $this->before_change($changed);
        
    //Let's update the dates
    foreach ($this->_table_columns as $col=>$v) 
      if (in_array($v['data_type'], array('timestamp','datetime','date'))) {
        if (!is_null($this->$col)) {
          if ($this->$col instanceof Daytime)
            $this->$col = self:: _db_date($this->$col->gettimestamp());
          else
            $this->$col = self:: _db_date($this->$col);
        }
      }
   
    if (isset($this->_table_columns['created']) && !$this->created) {
      $this->created = new Database_Expression('now()');
    }    
  
    if (isset($this->_table_columns['modified']) && !$this->modified) {
      $this->created = new Database_Expression('now()');
    }
    
    $this->test(true);
    
    $r = parent::save($validation);
    $this->reload();
    
    if ($is_new) {
      $this->on_insert($changed);
    } else {
      $this->on_update($changed);
    }
   
    $this->on_change($changed);
    
    return $r;
  
  }

  protected function _load_values(array $values) {

    //Let's update the dates
    foreach ($this->_table_columns as $col=>$v) {
    
      if (in_array($v['data_type'], array('timestamp','datetime','date'))) {

        if (empty($values[$col])) {
          $values[$col] = null;//new Daytime(null);
          continue;
        }

        if (is_numeric($values[$col])) {
         $values[$col] = new Daytime($values[$col]);  //Stupid but let's assume the DB is in UTC
         } else {
         $values[$col] = new Daytime($values[$col] . 'UTC');  //Stupid but let's assume the DB is in UTC
        }

      }
    }
    
    parent::_load_values($values);
    $this->on_load();
    return $this;
    
  }

	protected function _serialize_value($value) {
		return serialize($value);
	}

	protected function _unserialize_value($value)
	{
		return unserialize($value);
	}
	
	function set_array(Array $values, $restricted_to = null) {
  
    foreach ($values as $k=>$v) {
    
      if (is_array($restricted_to) && !in_array($k,$restricted_to)) {
        continue;
      }      
      
      $this->$k = $v;
    }
    
	}
	
	function to_array($extra_fields = Array()) {
  	return Array('id' => $this->id);
	}

}
