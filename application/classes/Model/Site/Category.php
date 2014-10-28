<?

/*
CREATE TABLE `site_categories` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  category_id int(11) unsigned,
  site_id int(11) unsigned,
  is_primary bool default false,
  PRIMARY KEY (`id`),
  unique(site_id, category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
*/

class Model_Site_Category extends Orm {
  
  protected $_belongs_to = Array(
    'site'      => Array('model'=>'site',    'foreign_key'=>'site_id'),
    'category'  => Array('model'=>'category','foreign_key'=>'category_id')
  );
 
}