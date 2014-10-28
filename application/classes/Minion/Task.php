<?

abstract class Minion_Task extends Kohana_Minion_Task {

  static function is_enabled() {
    return true;  
  }
  
	public static function convert_task_to_class_name($task) {
		$task = trim($task);

		if (empty($task))
			return '';

		return 'Minion_Task_'.implode('_', array_map('ucfirst', explode(Minion_Task::$task_separator, $task)));
	}


	/**
	 * Compiles a list of available tasks from a directory structure
	 *
	 * @param  array Directory structure of tasks
	 * @param  string prefix
	 * @return array Compiled tasks
	 */
	protected function _compile_task_list(array $files, $prefix = '')
	{
		$output = array();

		foreach ($files as $file => $path)
		{
			$file = substr($file, strrpos($file, DIRECTORY_SEPARATOR) + 1);

			if (is_array($path) AND count($path))
			{
				$task = $this->_compile_task_list($path, $prefix.$file.Minion_Task::$task_separator);

				if ($task)
				{
					$output = array_merge($output, $task);
				}
			}
			else
			{
			  $taskname =  strtolower($prefix.substr($file, 0, -strlen(EXT)));
        $classname = $this->convert_task_to_class_name($taskname);
        
        if ($classname::is_enabled()) {
          $output[] = $taskname;
        }
			}
		}

		return $output;
	}


  public function execute()
	{
	  if (!$this::is_enabled()) {
  	  die("THIS IS DISABLED");
	  }
	  
		$options = $this->get_options();

		// Validate $options
		$validation = Validation::factory($options);
		$validation = $this->build_validation($validation);

		if ( $this->_method != '_help' AND ! $validation->check())
		{
			echo View::factory('minion/error/validation')
				->set('task', Minion_Task::convert_class_to_task($this))
				->set('errors', $validation->errors($this->get_errors_file()));
		}
		else
		{
			// Finally, run the task
			
			$method = $this->_method;
			echo $this->{$method}($options);
		}
	}
	

}