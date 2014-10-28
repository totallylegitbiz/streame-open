<?

/*
DROP TABLE categories;
CREATE TABLE `categories` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  is_active bool default true,
  parent_id int(11) unsigned,
  display_order int unsigned default 0,
  PRIMARY KEY (`id`),
  unique(parent_id, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
*/

class Model_Category extends Orm {
  
  protected $_belongs_to = Array(
    'parent'      => Array('model'=>'category','foreign_key'=>'parent_id')
  );
  
  protected $_has_many = Array(
    'children'   => Array('model'=>'category','foreign_key'=>'parent_id'),
  );
  
  function before_change($changes) {
    if (empty($this->slug)) {
      $this->slug = Sanitize::tagify($this->name);  
    }
  }
  
  function add_child(Model_Category $child) {
    if (!$this->id) {
      throw new Exception('Category not loaded, cannot add child');
    }
    $child->parent_id = $this->id;
    $child->save(); 
  }
  
  function to_array( $params = Array() ) {
  
    $recurse = isset($params['recurse']) && $params['recurse'];
    
    $data = Array(
      'id'            => (integer) $this->id,
      'name'          => $this->name,
      'slug'          => $this->slug,
      'parent_id'     => $this->parent_id?(integer)$this->parent_id:null,
      'display_order' => (integer) $this->display_order,
      'path'          => '/c/' . $this->id . '/' . $this->slug
    );
    
    if ($recurse) {
      $children = Array();
      foreach($this->children->where('is_active','=', true)->order_by('display_order')->find_all() as $child) {
        $children[] = $child->to_array(Array('recurse'=>true));
      }
      $data['children'] = $children;
    }
    
    return $data;
  }
  
  
}