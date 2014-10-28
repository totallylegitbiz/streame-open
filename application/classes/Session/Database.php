<?

/**
 * Database-based session class.
 *
 * Sample schema:
 *
 *     CREATE TABLE  `sessions` (
 *         `session_id` VARCHAR( 24 ) NOT NULL,
 *         `last_active` INT UNSIGNED NOT NULL,
 *         `contents` TEXT NOT NULL,
 *         PRIMARY KEY ( `session_id` ),
 *         INDEX ( `last_active` )
 *     ) ENGINE = MYISAM ;
 *
 * @package    Kohana/Database
 * @category   Session
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license
 */
 
class Session_Database extends Kohana_Session_Database {

  protected $_ignore_cookies = false;

	protected function _read($id = NULL)
	{
  
		if ($id OR (!$this->_ignore_cookies && $id = Cookie::get($this->_name)))
		{
			$result = DB::select(array($this->_columns['contents'], 'contents'))
				->from($this->_table)
				->where($this->_columns['session_id'], '=', ':id')
				->limit(1)
				->param(':id', $id)
				->execute($this->_db);

			if ($result->count())
			{
			  // Set the current session id
				$this->_session_id = $this->_update_id = $id;

				// Return the contents
				return $result->get('contents');
			}
			
			if ($this->_ignore_cookies) {
			  $this->_data = [];
			  return NULL;
			}
			
		}

		// Create a new session id
		$this->_regenerate();

		return NULL;
	}

	protected function _write()
	{
		if ($this->_update_id === NULL)
		{
			// Insert a new row
			$query = DB::insert($this->_table, $this->_columns)
				->values(array(':new_id', ':active', ':contents'));
		}
		else
		{
			// Update the row
			$query = DB::update($this->_table)
				->value($this->_columns['last_active'], ':active')
				->value($this->_columns['contents'], ':contents')
				->where($this->_columns['session_id'], '=', ':old_id');

			if ($this->_update_id !== $this->_session_id)
			{
				// Also update the session id
				$query->value($this->_columns['session_id'], ':new_id');
			}
		}

		$query
			->param(':new_id',   $this->_session_id)
			->param(':old_id',   $this->_update_id)
			->param(':active',   $this->_data['last_active'])
			->param(':contents', $this->__toString());

		// Execute the query
		$query->execute($this->_db);

		// The update and the session id are now the same
		$this->_update_id = $this->_session_id;

		// Update the cookie with the new session id
		if (!$this->_ignore_cookies) {
		  Cookie::set($this->_name, $this->_session_id, $this->_lifetime);
    }
		return TRUE;
	}

	protected function _destroy()
	{
		if ($this->_update_id === NULL)
		{
			// Session has not been created yet
			return TRUE;
		}

		// Delete the current session
		$query = DB::delete($this->_table)
			->where($this->_columns['session_id'], '=', ':id')
			->param(':id', $this->_update_id);

		try
		{
			// Execute the query
			$query->execute($this->_db);

			// Delete the cookie
			if (!$this->_ignore_cookies) {
			  Cookie::delete($this->_name);
			}
		}
		catch (Exception $e)
		{
			// An error occurred, the session has not been deleted
			return FALSE;
		}

		return TRUE;
	}

} // End Session_Database
