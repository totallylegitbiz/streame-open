<?

Class Template {

  var $data = Array();
  var $tpl;
  var $paths = Array();
  
  function __construct() {
  
    $this->tpl = new Dwoo(); 
    
    $compile_dir = TPL_COMPILE_DIR.'/'.APP_NAME;
    
    if (!file_exists($compile_dir)) {
      mkdir($compile_dir,0775);
    }
    
    $this->tpl->setCompileDir($compile_dir);
  
  }
  
  function addPath($path) {
    $this->paths[] = $path;
  }  
  function assign ( $var, $value ) {
    $this->data[$var] = $value;
  }

  function get ( $tpl_file, $data = null, $include_paths = Array(), $default_app = APP_NAME ) {
    
    //If the data isn't set, use the template data
    if (!$data) {
      $data = $this->data;
    }
   
    $tpl = new Dwoo_Template_File($tpl_file);    
    
    if (!sizeof($include_paths)) {
      $this->paths[]  = APP_BASE.'/application/views/'.$default_app .'/';
      $this->paths[]  = APP_BASE.'/application/views/global/';
    } else {
      $this->paths = $include_paths;
    }
    
    $tpl->setIncludePath($this->paths);        
    
    $result = $this->tpl->get($tpl, $data);
      
    return $result;
  }

  function get_string ($string, $data = Array()) {
    
    $tpl = new Dwoo_Template_String($string);    
        
    //Render
    return $this->tpl->get($tpl, $data);
    
  }

}