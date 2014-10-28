<?


class HTTP_Exception extends Kohana_HTTP_Exception {
 
    /**
     * Generate a Response for all Exceptions without a more specific override
     * 
     * The user should see a nice error page, however, if we are in development
     * mode we should show the normal Kohana error page.
     * 
     * @return Response
     */
    public function get_response()
    {
        // Lets log the Exception, Just in case it's important!
        if ($this->getCode() != 404) {
          Logger::error("Exception: %o", $this);
        }
        
        if (Kohana::$environment >= Kohana::DEVELOPMENT)
        {
            // Show the normal Kohana error page.
            return parent::get_response();
        }
        else
        {
          die('404');
            // Generate a nicer looking "Oops" page.
            $view = View::factory('errors/default');
 
            $response = Response::factory()
                ->status($this->getCode())
                ->body($view->render());
 
            return $response;
        }
    }
}