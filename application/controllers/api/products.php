<?

Class Controller_Api_Products extends Api_Controller {

  function action_get() {
    
    $offset     = $this->request->param('offset', 0,  FILTER_VALIDATE_INT);
    $limit      = $this->request->param('limit',  10, FILTER_VALIDATE_INT); 
    
    $price_min  = $this->request->param('price_min',  null, FILTER_VALIDATE_INT, ['allow_null'=>true]); 
    $price_max  = $this->request->param('price_max',  null, FILTER_VALIDATE_INT, ['allow_null'=>true]); 
    
    $query      = $this->request->param('query',   null, FILTER_VALIDATE_STR, ['allow_null'=>true]);   
    $source     = $this->request->param('source',  null, FILTER_VALIDATE_STR, ['allow_null'=>true]); 
    
    $only_free_ship  = $this->request->param('only_free_ship',   false, FILTER_VALIDATE_BOOLEAN, ['allow_null'=>true]);
    $only_available  = $this->request->param('only_available',   true,  FILTER_VALIDATE_BOOLEAN, ['allow_null'=>true]);
        
   
    $sort      = [];
    
    $offer_queries = [];
  
    if ($only_available) {
      $offer_queries[] = ["match"=>["offers.available" => true]];
    }
      
    if (!is_null($price_min)) {
      $offer_queries[] = [
        "range" => ["offers.sale_price"=>["gte"=>$price_min * 100]]
      ];
    }
    if (!is_null($price_max)) {
      $offer_queries[] = [
        "range" => ["offers.sale_price"=>["lte"=>$price_max * 100]]
      ];
    }    
    
    if ($only_free_ship) {
      $offer_queries[] = [
        "match" => ["offers.has_super_saver"=>true]
      ];
    }
    
    $es_query['query']['filtered']['query'] = [];
    if (count($offer_queries)) {
    
      $es_query['query']['filtered']['query'][] = [
        "nested" => [
          "path"  => "offers",
          "query" => [
            "bool" => [
              "must" => $offer_queries
            ]
          ] 
        ]
      ];
    }
    
    if (!is_null($query)) {
      $es_query['query']['filtered']['query'][] = [
        'query_string' => 
        [
          "query"  => $query,
/*           "fields" => ["title^5", "description"] */
        ]
      ];
    }
    
/*   print_r($es_query);exit; */
    $es_query['sort'] = [
      ["score" => ["order" => "desc"]]
    ];
    
    Logger::debug("Query: %o", $es_query);
    $items = Orm('item')->search($es_query, $limit, $offset);
    
    $this->response->payload = ['products'=>$items, 'product_hits'=>$items->total_hits];
    
  }
  
}

