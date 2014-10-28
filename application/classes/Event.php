<?

Class Event {

  static $mongo;
  static $cache;
  const EVENT_DB                     = 'event';
  const EVENT_COLLECTION             = 'events';
  const EVENT_AGGR_COLLECTION_PREFIX = 'events_';
 
  static $data_groups         = Array('MINUTE'=> MINUTE, 'HOUR' => HOUR, 'DAY'=>DAY, 'WEEK'=>WEEK,'TOTAL'=>0);

  static function init() {

    if (self::$mongo == null) {
      self::$mongo = new MongoClient(MONGO_SERVER);  
      self::$mongo->selectDb(Event::EVENT_DB);
    }

    if (self::$cache == null) {
      self::$cache = new Cache_Remote();
    }

  }
 
  static function clean_up() {
  
  
    try {
      self::init();
    } catch (Exception $e) {
    
      if (IS_DEV) {
       throw $e;
      }
    
      App_Config::set('stats_enable_mongo',0);
      //stats_enable_graphs
      Logger::error("Mongo error, disabling:" . $e->getMessage());
      
      return;
    }


    $start_ts       = time();

    foreach (self::$data_groups as $ts_group=>$sec) {
    
      if (!$sec || $sec >= WEEK) {
        continue;
      }
      
      $event_collection   = self::$mongo->selectDB( self::EVENT_DB )->selectCollection( self::EVENT_AGGR_COLLECTION_PREFIX .  strtolower($ts_group) );
      
      $cutoff    = $start_ts - (sqrt($sec)*100000);
      
      $condition = Array('ts'=>Array('$lte'=>$cutoff));
          
      $event_collection->remove($condition);
       
    }
  
  } 
  
  /*
static function add( $type, Array $targets = Array(), $tally_inc = 1, $ts = null) {
  
    if (!$ts) {
      $ts = time();
    }
    
    Task::queue(function($type, $targets, $tally_inc, $ts) {
      Event::_add($type, $targets, $tally_inc, $ts);
    }, Array($type, $targets, $tally_inc, $ts), Task::PRIORITY_LOW);
    
  }
*/
  
  static function add ( $type, Array $targets = Array(), $tally_inc = 1, $ts = null) {
  
    return;
    if (!App_Config::get('stats_enable_mongo')) {
      return;
    }
    
    /** Raw, un-aggregated  **/ 
    
    $ts = $ts?$ts:time();
    
    try {
      self::init();
    } catch (Exception $e) {
    
      if (IS_DEV) {
      //  throw $e;
      }
    
      //Config::set('stats_enable_mongo',0);
      //stats_enable_graphs
      
      Logger::error("Mongo error:" . $e->getMessage());
      
      return;
    }
    
    $tally_inc = 1*$tally_inc;
    
    if (!$tally_inc) {
      //Just twittling our thumbs
      return false;
    }
    
    $event_collection   = self::$mongo->selectDB( self::EVENT_DB )->selectCollection( self::EVENT_COLLECTION );

    $doc['targets']     = $targets;
    $doc['type']        = $type;
    $doc['ts']          = (integer)$ts;
    $doc['tally']       = 1*$tally_inc;
    $doc['cnt']         = 1;
    
    //It's annoying, I know.    
    foreach ($targets as $target=>$target_id) {
      if (is_array($target_id)) {
        $targets[$target] = Array();
        
        foreach ($target_id as $t_id) {
          $targets[$target][] = coerce($t_id);
        }
        
      } else {
       $targets[$target] = coerce($target_id);
      }
    }
        
    /** Aggregated Data **/
      
    foreach (self::$data_groups as $ts_group=>$sec) {
    
      $event_collection   = self::$mongo->selectDB( self::EVENT_DB )->selectCollection( self::EVENT_AGGR_COLLECTION_PREFIX .  strtolower($ts_group) );
       
      $event_collection->ensureIndex( Array( 'ts' => 1, 'type'=>1 ), Array('background'=>true) ); 
      $event_collection->ensureIndex( Array( 'key' => 1),            Array('background'=>true) ); 
                
      //If $sec == 0, then we just have a single column with totals.
      if ($sec) {
        $ts = (integer) $ts - ($ts % $sec);
      } else {
        $ts = 0;
      }
      
      $event_cols = Array(
        'ts'        => $ts, 
        'type'      => $type,
        'ts_group'  => $ts_group,
        'targets'   => $targets
      );
      
      $event_key         = Sanitize::keyify($event_cols);
      $event_cols['key'] = $event_key;
      
      $event = Array(
        '$set' => $event_cols,
        '$inc' => Array('tally'=>$tally_inc,'cnt'=>1)
      );
      
      $r = $event_collection->update(Array('key'=>$event_key), $event, array("upsert" => true, "safe"=>false));
    
    }
    
    return true;

  }


  static function top_target( $type, $group_by_target, $target_filters = Array(), $last_ts = WEEK, $group_col = 'DAY') {
    
    $start_ts   = time() - $last_ts;
    $end_ts     = time();
    
    self::init();
    
    $event_collection   = self::$mongo->selectDB( self::EVENT_DB )->selectCollection( self::EVENT_AGGR_COLLECTION_PREFIX .  strtolower($group_col) );

    $query = Array('type'=>$type, 'ts' => Array( '$gte'=>$start_ts, '$lte' => $end_ts ));
  
    foreach ($target_filters as $target=>$value) {
      $query['targets.' . $target] = coerce($value);
    }
  
    $index = Array();
    
    foreach ($query as $field=>$val) {
      $index[$field] = 1;
    }
    
    $event_collection->ensureIndex( $index, Array('background'=>true) );    
    
    $events = $event_collection->find( $query, Array('targets', 'tally', 'cnt') );
    
    $out = Array(); 
    
    foreach ($events as $event) {
      
       if (!isset($event['targets'][$group_by_target])) {
        continue;
       }
       
       
       $group_by_targets = $event['targets'][$group_by_target];
    
       
       if (!is_array($group_by_targets)) {
        $group_by_targets = Array($group_by_targets);
       }
       
      
       
       foreach ($group_by_targets as $i) {
       
        if (!isset($out[$i])) {
          $out[$i] = 0;
        }
        
        $out[$i] += $event['tally'];
        
       }

    }
    
    arsort($out, SORT_NUMERIC);
    
    return $out;
    
  }
  static function stats ( $types = Array(), $targets = Array(), $ts_group, $start_ts = null, $end_ts = null, $return = 'tally', $zero_fill = true ) {
  
    self::init();
    
    if (!is_array($types)){
      $types = Array($types);
    }
    
    $group_sec = $group_col = null;
    
    foreach (self::$data_groups as $group=>$sec) {
    
      if ($ts_group == $sec) {
        $group_sec = $sec;
        $group_col = $group;
      }
    
    }
    if (is_null($group_sec)) {
      throw new Exception('Did not find data_group');
    }

    //Sure, that's one day to do a mod
    
    $query = Array('type' => Array('$in'=>$types));
    
    if ($group_sec) {
      $start_ts    = floor($start_ts / $group_sec) * $group_sec;
      $end_ts      = ceil($end_ts / $group_sec)    * $group_sec;
      $query['ts'] = Array( '$gte'=>$start_ts, '$lte' => $end_ts );
    }    
    
    $event_collection   = self::$mongo->selectDB( self::EVENT_DB )->selectCollection( self::EVENT_AGGR_COLLECTION_PREFIX .  strtolower($group_col) );
    
    foreach ($targets as $target=>$target_id) {
      if (is_array($target_id)) {
        $values = Array();
        
        foreach ($target_id as $t_id) {
          $values[] = coerce($t_id);
        }
        
        $query['targets.' . $target] = Array('$in'=>$values);
        
      } else {
        $query['targets.' . $target] = coerce($target_id);
      }
    }
   
    $index = Array();
    
    foreach ($query as $field=>$val) {
      $index[$field] = 1;
    }
    
    $event_collection->ensureIndex( $index, Array('background'=>true) );      
   
    $reduce = 'function(obj,prev) { prev.tally += obj.tally;prev.cnt += obj.cnt; if (prev.cnt>0) { prev.avg = prev.tally / prev.cnt } else {prev.avg = 0} }';
    
    $start_mt = microtime(true);
    $events = @$event_collection->group(
      Array('ts'=>true,'type'=>true),
      Array('tally'=>0, 'cnt'=>1,'avg'=>0),
      $reduce,
      $query
    );
    
    
    $group_log = Array('key'=>Array('ts'=>true,'type'=>true),'cond'=>$query, 'reduce'=>$reduce, 'initial'=>Array('tally'=>0,'cnt'=>0));
    //Logger::debug("Event:stat %s.group(%s) %d ms",  'db.'.self::EVENT_AGGR_COLLECTION_PREFIX.strtolower($group_col),json_encode($group_log), (microtime(true)-$start_mt)*1000);
    
    $out = Array();
    foreach ($types as $type) {
      
      if ($group_sec) {
        $out[$type] = Array();
      } else {
        $out[$type] = 0;
      }
    }
    
    foreach ($events['retval'] as $row) {
    
      //Let's make sure the index is set first.
      
      if (!isset($out[$row['type']])) {
        $out[$row['type']] = Array();
      }
      
      if (!$group_sec) {
        $out[$row['type']] = $row[$return];
        continue;
      }
      
      if (!isset($out[$row['type']][$row['ts']])) {
        $out[$row['type']][$row['ts']] = 0;
      }
    
      $out[$row['type']][$row['ts']] += $row[$return];
      
    }
    
    if ($zero_fill && $group_sec) {
    
      //Now let's loop through all the possible times.
      
      for ($i = $start_ts; $i < $end_ts; $i += $ts_group ) {
        foreach ($types as $type) {
          if (!isset($out[$type])) {
            $out[$type] = Array();
          }
          
          if (!isset($out[$type][$i])) {
            $out[$type][$i] = 0;
          }
          
        }
      
      }
      
      
      foreach ($types as $type) { 
        ksort($out[$type],SORT_NUMERIC);
      }
      
    
    }
          
    return $out;

  }
  
  
  static function old_stats ( $types = Array(), $targets = Array(), $ts_group, $start_ts = null, $end_ts = null, $return = 'tally', $zero_fill = true ) {
  
    self::init();
    
    if (!is_array($types)){
      $types = Array($types);
    }
    
    $group_sec = $group_col = null;
    
    foreach (self::$data_groups as $group=>$sec)
      if ($ts_group == $sec) {
        $group_sec = $sec;
        $group_col = $group;
      }
    
    if (is_null($group_sec)) {
      throw new Exception('Did not find data_group');
    }

    //Sure, that's one day to do a mod
    
    $query = Array('type' => Array('$in'=>$types));
    
    if ($group_sec) {
      $start_ts    = floor($start_ts / $group_sec) * $group_sec;
      $end_ts      = ceil($end_ts / $group_sec)    * $group_sec;
      $query['ts'] = Array( '$gte'=>$start_ts, '$lte' => $end_ts );
    }    
    
    $event_collection   = self::$mongo->selectDB( self::EVENT_DB )->selectCollection( self::EVENT_AGGR_COLLECTION_PREFIX .  strtolower($group_col) );
    
    foreach ($targets as $target=>$target_id) {
      if (is_array($target_id)) {
        $values = Array();
        
        foreach ($target_id as $t_id) {
          $values[] = coerce($t_id);
        }
        
        $query['targets.' . $target] = Array('$in'=>$values);
        
      } else {
        $query['targets.' . $target] = coerce($target_id);
      }
    }
   
    $index = Array();
    
    foreach ($query as $field=>$val) {
      $index[$field] = 1;
    }
    
    $event_collection->ensureIndex( $index, Array('background'=>true) );      
   
    $reduce = 'function(obj,prev) { prev.tally += obj.tally;prev.cnt += obj.cnt; if (prev.cnt>0) { prev.avg = prev.tally / prev.cnt } else {prev.avg = 0} }';
    
    $start_mt = microtime(true);
    $events = @$event_collection->group(
      Array('ts'=>true,'type'=>true),
      Array('tally'=>0, 'cnt'=>1,'avg'=>0),
      $reduce,
      $query
    );
    
    
    $group_log = Array('key'=>Array('ts'=>true,'type'=>true),'cond'=>$query, 'reduce'=>$reduce, 'initial'=>Array('tally'=>0,'cnt'=>0));
    //Logger::debug("Event:stat %s.group(%s) %d ms",  'db.'.self::EVENT_AGGR_COLLECTION_PREFIX.strtolower($group_col),json_encode($group_log), (microtime(true)-$start_mt)*1000);
    
    $out = Array();
    foreach ($types as $type) {
      
      if ($group_sec) {
        $out[$type] = Array();
      } else {
        $out[$type] = 0;
      }
    }
    
    foreach ($events['retval'] as $row) {
    
      //Let's make sure the index is set first.
      
      if (!isset($out[$row['type']])) {
        $out[$row['type']] = Array();
      }
      
      if (!$group_sec) {
        $out[$row['type']] = $row[$return];
        continue;
      }
      
      if (!isset($out[$row['type']][$row['ts']])) {
        $out[$row['type']][$row['ts']] = 0;
      }
    
      $out[$row['type']][$row['ts']] += $row[$return];
      
    }
    
    if ($zero_fill && $group_sec) {
    
      //Now let's loop through all the possible times.
      
      for ($i = $start_ts; $i < $end_ts; $i += $ts_group ) {
        foreach ($types as $type) {
          if (!isset($out[$type])) {
            $out[$type] = Array();
          }
          
          if (!isset($out[$type][$i])) {
            $out[$type][$i] = 0;
          }
          
        }
      
      }
      
      
      foreach ($types as $type) { 
        ksort($out[$type],SORT_NUMERIC);
      }
      
    
    }
          
    return $out;

  }
  
  
  
  static function graph ($types, $targets, $ts_group, $start_ts, $end_ts, $div_selector = '', $return = 'tally', $divisor = 1, $raw_data = false) {
    
    if (!App_Config::get('stats_enable_mongo')) {
      return;
    }
        
        $tmp_types = Array();
        
        if(is_array($types)) {
          foreach ($types as $label=>$type) {
            if (!is_numeric($label)) {
              $event_names[$type] = $label;
            }
            
            $tmp_types[] = $type;
          }
        } else {
          $types = Array($types);
        }
      
        $types = $tmp_types;
        
        $out = Array();
        
        $result = Event::stats($types, $targets, $ts_group,$start_ts, $end_ts, $return);
        
        foreach ($result as $type=>$data) {
    
          $plot_data = Array();
    
          foreach ($data as $ts=>$val) {
            //Multiply by 1k
            $plot_data[] = Array((double) ($ts) * 1000, $val / $divisor);
          }
    
          $out[] = Array('data'=>$plot_data, 'label' => ifset($event_names[$type],$type));
    
        }
    
        //#{$div_id}
        
        //If no selector is given, draw one
        $out_text = '';
        if (!strlen($div_selector)) {
          $div_id = 'stat_' . md5(microtime() . rand(0,10000009));
          $out_text .= "<div id=\"{$div_id}\" class='graph'></div>";
          $div_selector = '#' . $div_id;
        } 
        
        $colors = Array('#8bd2ea','#fFC898','#F1B7E9','#B2DcC1','#Ff9c90');
        
        //Maybe this is too much work just to have a consistant color scheme
        $index = str_hash(sizeof($colors),serialize($targets) . serialize($ts_group));
        
        $new_colors = Array();
        
        for ($i=$index;$i<$index+sizeof($colors);$i++) {
          $new_colors[] = $colors[$i%sizeof($colors)];
        }
        
        $colors = '\'' . implode('\',\'',$new_colors) .'\'';
        
        if ($raw_data) {
          return $out;
        }
        
        return $out_text . "
        
    <script language='javascript'>
    
    var previousPoint = null;
    
    var draw_div = function () {
    
      var options = {
        xaxis: { mode: \"time\",
        tickFormatter: function (val, axis) {
          var d = new Date(val);
                    
          if (d.getHours()+d.getMinutes() == 0 || " . ($ts_group == DAY?1:0) . ") {
            
            if (d.getHours() == 1 && d.getMonth()) {
              format = 'UTC:mmm d, yyyy'
            } else {
              format = 'UTC:mmm d'
            }
            
          } else {
            format = 'shortTime';
          }
    
          return d.format(format);
        }
        },
        yaxis: {min:0.0, tickDecimals: 0},
        points: { show: true,  lineWidth: 1,radius: 3, shadowSize:0 },
        lines:  { show: true,  fill:true, lineWidth: 3, shadowSize: 0},
        colors: [{$colors}],
        shadowSize: 0,
        grid: { hoverable: true, borderWidth: 1},
        legend: {
          position: 'nw'
        }
    
      };
    
      var plot = $.plot($(\"{$div_selector}\"), " .  json_encode($out) . " , options);
    
      $(\"{$div_selector}\").bind(\"plothover\", function (event, pos, item) {
    
        if (item) {
          if (previousPoint != item.datapoint) {
          
              previousPoint = item.datapoint;
      
              $(\"#graph_tooltip\").remove();
              
              var x = item.datapoint[0].toFixed(0);
              var y = item.datapoint[1].toFixed(0);
              
              $('<div id=\"graph_tooltip\">' + y + '</div>').css( {
                position: 'absolute',
                display: 'none',
                'font-size': '8pt',
                top: item.pageY - 25,
                left: item.pageX - 5,
                border: '1px solid #fdd',
                padding: '2px',
                'background-color': '#fee',
                opacity: 0.80
              }).appendTo(\"body\").show();
            
            }
        }
        else {
          $(\"#tooltip\").remove();
          previousPoint = null;
        }
      });
    
    
    
    }
    
    $(draw_div);
    
    </script>";
    
    }


  
}
