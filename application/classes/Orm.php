<?

Class Orm extends Orm_Mysql {
  
  public function before_insert($changed) {}
  public function before_update($changed) {}
  public function on_insert($changed) {}
  public function on_update($changed) {}
  public function on_change($changed) {}
  public function on_delete($id) {}
  public function before_delete() {}
  public function before_change($changed) {}
  public function on_load() {}

  public function sanitize_filters() { return []; }
  public static function factory($model, $id = NULL) {

    $model = str_replace('_', ' ', $model);
    $model = ucwords($model);
    $model = str_replace(' ', '_', $model);
    
    $model = 'Model_'.ucwords($model);
    
 		return new $model($id);
 		
  }
 
  static function __callStatic($name, $arguments) {
    $id = null;
    if (count($arguments)) {
      $id = $arguments[0];
    }
    
    return Orm::factory($name, $id);
  }
  
 	function to_array($extra_fields = []) {
  	return ['id' => $this->id];
	}
	
	protected function _serialize_value($value) {
		return serialize($value);
	}

  
	protected function _unserialize_value($value) {
		return unserialize($value);
	}
	
  function set_array(Array $values, $restricted_to = null, $empty_as_null=true) {
  
    foreach ($values as $k=>$v) {
    
      if (is_array($restricted_to) && !in_array($k,$restricted_to)) {
        continue;
      }      
      if ($empty_as_null && $v === '') {
        $v = null;
      }
      $this->$k = $v;
    }
    
	}
	
	function extra_test($errors) {
    return $errors;	
	}
	
  function get_object_name() {
    return $this->_object_name;
  }
  
	function test( $throw_exception = false) {
	  $errors = [];
	  foreach ($this->sanitize_filters() as $column=>$filters) {
	    foreach ($filters as $filter) {
	      //$value, $default = null, $filter = null, $options 
	      try { 
	        if (!isset($filter[3])) {
  	        $filter[3] = null;
	        }
          $this->$column = Sanitize::filter($column, $this->$column, $filter[3], $filter[0], $filter[1]);
        } catch (Except_Filter $e) {
          if (!isset($filter[2]) || is_null($filter[2])) {
            $errors[$column] = $e->getMessage();
          } else {
            $errors[$column] = $filter[2];
          }
        }
      }
  	}
  	
  	$errors = $this->extra_test($errors);
  	
  	if ($throw_exception && count($errors)) {
    	$exception = new Orm_Filter_Exception;
    	$exception->errors = $errors;
    	throw $exception;
  	}
  	
  	return $errors;
	}
	
	function delete_all() {
    foreach ($this->find_all() as $row) {
      $row->delete();
    }
	}
	
}