<?

class Model_User_Taste extends Orm {
   
  protected $_belongs_to = [
  'user'      => ['model'=>'user','foreign_key'=>'user_id']
  ];
  
  function recalculate (Model_User $user) {
    $this->where('user_id', '=', $user->id)->delete_all();
    
    $actions = Orm::user_action()
      ->where('user_id', '=', $user->id)
      ->group_by('target_type')
      ->group_by('target_id')
      ->order_by('created','desc')
      ->find_all();
      
    $type_scores = [
      'view'    =>  1,
      'dislike' => -5,
      'like'    =>  3,
    ];
    
    $scores      = [];
    $matches     = [];
    foreach ($actions as $action) {
    
      $target      = $action->target();
      $attributes  = $target->attributes();
      $action_type = $action->action;
      
      foreach ($attributes as $key=>$values) {

        //If this is not an array, then it's a single value
        if (!is_array($values)) {
        
          if (!isset($scores[$key])) {
            $scores[$key] = $matches[$key] = [];
          }
                  
          if (!isset($scores[$key][$values])) {
            $matches[$key][$values] = $scores[$key][$values] = 0;
          }
          
          $scores[$key][$values] += $type_scores[$action_type] * 1; //Default to 1
          $matches[$key][$values]++;
        } else {
          
          foreach ($values as $sub_values) {
            if (!is_array($sub_values)) {
              $score = 1;
            } else {
              $sub_attribute = $sub_values[0];
              $score         = $sub_values[1];
            }
            
            if (!isset($scores[$key][$sub_attribute])) {
              $scores[$key][$sub_attribute] = $matches[$key][$sub_attribute] = 0;
            }
            $matches[$key][$sub_attribute]++;
            $scores[$key][$sub_attribute] += $type_scores[$action_type] * $score;
          }
          
        }
        

      }

    }
    
/*
+---------+------------------+------+-----+-------------------+----------------+
| Field   | Type             | Null | Key | Default           | Extra          |
+---------+------------------+------+-----+-------------------+----------------+
| id      | int(11) unsigned | NO   | PRI | NULL              | auto_increment |
| user_id | int(11) unsigned | NO   | MUL | NULL              |                |
| feature | varchar(20)      | NO   |     | NULL              |                |
| value   | varchar(100)     | NO   |     | NULL              |                |
| score   | float            | NO   |     | NULL              |                |
| matches | float unsigned   | NO   |     | 0                 |                |
| created | timestamp        | NO   |     | CURRENT_TIMESTAMP |                |
+---------+------------------+------+-----+-------------------+----------------+
*/

    foreach ($scores as $key=>$values) {
      foreach ($values as $value=>$tmp) {
        $taste = new Model_User_Taste();
        $taste->user_id = $user->id;
        $taste->feature = $key;
        $taste->value   = $value;
        $taste->matches = $matches[$key][$value];
        $taste->score   = $scores[$key][$value];
        $taste->save();
      }
    }
  }
  
  
  function get_results(Model_User $user, $query, $limit, $offset) {

    $terms = [
      'status'      => 'ACTIVE',
    ];
    
    $filters = [];
        
    foreach ($terms as $k=>$v) {
      $filters[] = ["term" => [$k => $v]];
    }
    
    $sort       = ["_score"];
    $boost_functions = [];
    
    foreach ($this->where('user_id', '=', $user->id)->find_all() as $taste) {
      $boost_functions[] = [
        "filter"       =>["term" => [ $taste->feature => [ "value" => $taste->value]]],
        "boost_factor" => $taste->score,
      ];
    }
    
   $boost_functions[] = [
    "gauss" => ["published" => ["scale"=> "3d"]]
   ];
   
   $es_query = [
      "query" => [
        "function_score" =>
          [
            "functions"  => $boost_functions,
            "score_mode" => "multiply"
          ],
        /*"constant_score" => [
            "filter" => [
                ["and" => $filters],
              ]
          ],*/
         "sort" => $sort,
         "size" => $limit,
         "from" => $offset
       ],

    ];

    Logger::debug("Query: Limit: %o, Offset: %o, %o", $limit, $offset, $es_query);
    return Orm::site_post()->search($es_query, $limit, $offset);    
  }
}


/*
CREATE TABLE `user_tastes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `feature` varchar(20) NOT NULL,
  `value` varchar(100) NOT NULL,
  `score` float NOT NULL,
  `matches` float unsigned NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `test_id_feature_value` (`user_id`,`feature`,`value`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
*/
