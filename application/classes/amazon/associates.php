<?

use ApaiIO\Configuration\GenericConfiguration;
use ApaiIO\Operations\Lookup;
use ApaiIO\ApaiIO;
use ApaiIO\Operations\Search;

Class Amazon_Associates {
  
  var $service;
  var $conf;
  
  function __construct() {
 
    $this->conf = new GenericConfiguration();
    $this->conf->setCountry('com')
         ->setAccessKey(AWS_ACCESS_ID)
         ->setSecretKey(AWS_ACCESS_KEY)
         ->setAssociateTag(AWS_ASSOCIATE_TAG);
    
    $this->service = new ApaiIO($this->conf);

  }
  
  function lookup($asin) {
  
    $lookup = new Lookup();
    $lookup->setItemId($asin);
    $lookup->setResponseGroup(array('Large', 'Small'));
    
    $xml = $this->service->runOperation($lookup, $this->conf);
    
    return  new SimpleXMLElement($xml);
  }
  
  function search($query) {
  
    $search = new Search();
    $search->setKeywords($query);
    $search->setSearchIndex('All');
/*     $search->setPage(1); */
    $search->setResponseGroup(array('Large', 'Small'));
    $xml = $this->service->runOperation($search, $this->conf);
    return new SimpleXMLElement($xml);
  }
  function item_array($item) {
    // number_format($price / 100,2)
    $offers = [];
    
    foreach ($item->Offers as $offer) {
    
      $tmp_offer['sale_price']      = (integer) $offer->Offer->OfferListing->SalePrice->Amount;
      $tmp_offer['price']           = (integer) $offer->Offer->OfferListing->Price->Amount;
      $tmp_offer['listing_id']      = (string) $offer->Offer->OfferListing->OfferListingId;
      $tmp_offer['available']       = $offer->Offer->OfferListing->AvailabilityAttributes->AvailabilityType == 'now';
      $tmp_offer['has_super_saver'] = (bool) $offer->Offer->OfferListing->IsEligibleForSuperSaverShipping;
       
      if (!$tmp_offer['sale_price']) {
        $tmp_offer['sale_price'] = $tmp_offer['price'];
      }
      
      if (!$tmp_offer['price']) {
        $tmp_offer['price'] = $tmp_offer['sale_price'];
      }
      
      $offers[] = $tmp_offer;
      
    }

      $out = [
      'id'          => (string) $item->ASIN,
      'network'     => 'AMAZON',
      'url'         => (string) urldecode($item->DetailPageURL),
      'title'       => (string) $item->ItemAttributes->Title,
      'description' => (string) $item->EditorialReviews->EditorialReview->Content,
      'image_main'  => (string) urldecode($item->LargeImage->URL),
      'images'      => [],
      'offers'      => $offers
    ];
        
    foreach($item->ImageSets as $ImageSets) {
      foreach($ImageSets as $ImageSet) {
        $out['images'][] = (string) $ImageSet->LargeImage->URL;
      }
    }

    return $out;

  }
  
  static function get_asin_from_url($url) {
    
    if (preg_match('/\/([A-Z0-9]{10})(\/|$)/', $url, $matches)) {
      return $matches[1];
    }
    
    return null;
    
    
    
  }
}

