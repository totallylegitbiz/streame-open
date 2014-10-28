<?

Route::set('images', 'images/<size>/<hash>/<filename>.<ext>', Array())
->defaults(array(
	'controller' => 'images',
	'action'     => 'index'
));
	
Route::set('default', '<path>', ['path'=> '.*'])
	->defaults([
		'controller' => 'index',
		'action'     => 'index',
	]);
	