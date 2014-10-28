<?

Class Orm_Search_Result implements Iterator, Countable {

  //private position, har har
  private $position = 0;

  var $total_hits   = 0;
  var $docs         = Array();
  var $facets       = Array();
  
  function rewind() {
    $this->position = 0;
  }
  
  function current() {
    return $this->docs[$this->position];
  }
    
  function key() {
    return $this->position;
  }
  
  function next() {
    ++$this->position;
  }
  
  function valid() {
    return isset($this->docs[$this->position]);
  }
  
  function count() {
    return sizeof($this->docs);
  }
  
}