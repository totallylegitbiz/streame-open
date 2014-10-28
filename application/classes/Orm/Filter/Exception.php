<?

Class Orm_Filter_Exception extends Exception {
  var $errors = [];
  function errors() {
    return $this->errors;
  }
}