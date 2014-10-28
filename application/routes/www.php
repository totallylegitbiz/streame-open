<?


/*
Route::set('util', 'util/<action>', ['action' => '(sns|twillio)'])
	->defaults([
		'controller' => 'util'
	]);
	
*/
	
	
	
/*
Route::set('default', '(<controller>(/<action>(/<id>)))')
	->defaults([
		'controller' => 'index',
		'action'     => 'index',
	]);
	
*/

Route::set('auth', 'u/<action>(/<option>)', Array('action' => '(login|logout|connect|signup)'))
	->defaults(array(
		'controller' => 'user'
	));
	

Route::set('images', 'images/<size>/<hash>/<filename>.<ext>', Array())
->defaults(array(
	'controller' => 'images',
	'action'     => 'index'
));


Route::set('default', '')
	->defaults([
		'controller' => 'index',
		'action'     => 'index',
]);


/*
Route::set('default', '<path>', ['path'=> '.*'])
	->defaults([
		'controller' => 'index',
		'action'     => 'index',
]);
*/
