<?php
/**
 * Extra HTTP Status Code Messages
 */
Response::$messages[102] = 'Processing';
Response::$messages[207] = 'Multi-Status';
Response::$messages[422] = 'Unprocessable Entity';
Response::$messages[423] = 'Locked';
Response::$messages[424] = 'Failed Dependency';
Response::$messages[507] = 'Insufficient Storage';

/**
 * Routes
 */

Route::set('api', 'api/<controller>(/<id>)(/<custom>(/<custom_id>))', array('id' => '\d+'))
	->defaults(array(
		'directory'  => 'api',
		'id'         => FALSE,
		'custom_id'  => FALSE,
		'action'     => 'index',
	));
