<?

return [
	'default' => [
  	'benchmark'     => FALSE,
  	'persistent'    => FALSE,
  	'type'          => 'PDO',
  	'connection'    => [
  	  'dsn'      => 'mysql:host=localhost;dbname=streame',
  		'username' => 'app',
  		'password' => '',
  	],
  	'character_set' => 'utf8',
  	'table_prefix'  => '',
  	'object'        => TRUE,
  	'cache'         => TRUE,
  	'escape'        => TRUE
  ]
];
