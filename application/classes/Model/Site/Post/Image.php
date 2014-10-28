<?

class Model_Site_Post_Image extends Orm {
  
   protected $_belongs_to = Array(
     'site'      => Array('model'=>'site',     'foreign_key'=>'site_id'),
     'post'      => Array('model'=>'site_post','foreign_key'=>'site_post_id'),
     'image'     => Array('model'=>'image',    'foreign_key'=>'image_id'),
  );
  
}