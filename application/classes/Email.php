<?

/*
    $email = new Email();
    
    echo $email->send_template('jorge@bellaga.com', 'test.html', Array('name'=>"You"));
    exit;
*/

Class Email {

  static function send_template ( $to, $tpl_file, $vars = Array(), $directory = 'email/', $app = 'default') {
  
    $template       = new Template();
    
    $vars['_email']     = $to;
    
    $properties = Array(
      'distinct_id'    => $to,
      'mp_name_tag'    => $to,
      'email template' => $tpl_file
    );
    
    $vars['_tracking_pixel'] = Mixpanel::event_pixel('Email View', $properties);
    
    $body = $template->get ( $directory.$tpl_file . '.html', $vars, Array(), $app );
    
    $pieces = explode("\n--==--\n",$body,3);
    
    if (!sizeof($pieces) == 3) {
      throw new Exception('Something wrong with this email, pieces not found');
    }
    
    list($header, $html, $text) = $pieces;
    
    if (preg_match('/^Subject:(.*)$/', $header, $matches)) {
      $subject = trim($matches[1]);
    }
    
    $html = self::add_email_tracking($properties, $html);
    
    Mixpanel::event('Email Sent', $properties);
     
    return self::send($to, 'hi@woo.ly', 'Sparkpet', $subject, $html, $text);
    
  }  
  
  static function add_email_tracking($properties, $html) {
        
    $dom = new DOMDocument;
    $dom->strictErrorChecking = false; 
    @$dom->loadHTML($html);
    
    $el_as = $dom->getElementsByTagName('a');
    
    for ($i = $el_as->length; --$i >= 0; ) { 
      
      $a = $el_as->item($i);
      
      //Let's see if this image even have a source, if not, remove it and move on.
      if (!$a->hasAttribute('title') || !$a->hasAttribute('href')) {
        continue;
      }
      
      $properties['link name']  = $a->getAttributeNode('title')->value;
      
      $click_through_url        = Mixpanel::event_redirect('Email Click', $properties, $a->getAttributeNode('href')->value);
      
      $a->setAttributeNode(new DOMAttr('href',  $click_through_url));
      
    }
    
    return $dom->saveHTML();
    
  }
  
  static function send($to, $from, $from_name, $subject, $html, $text) {
    
    if (IS_DEV) {
      $subject = '[DEV '. $to . '] ' . $subject;
      $to = DEV_EMAIL;
    }
    
    Logger::debug("Sending email: %o - %o", $to, $subject);
    
    $mail = new SendGrid\Mail();
    $mail->addTo($to)->
      setFrom($from)->
      setFromName($from_name)->
      setSubject($subject)->
      setText($text)->
      setHtml($html);
    
    $sendgrid = new SendGrid(SENDGRID_API_USER, SENDGRID_API_PASS);
    
    return $sendgrid->smtp->send($mail);
    
  }
  
}
