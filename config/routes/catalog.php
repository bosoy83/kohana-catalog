<?php defined('SYSPATH') or die('No direct script access.');

return array (
	'catalog' => array(
		'uri_callback' => array('Helper_Catalog', 'route'), 
		'regex' => '(/<category_uri>(/<element_uri>))(?<query>)',
		'defaults' => array(
			'directory' => 'modules',
			'controller' => 'catalog',
			'action' => 'index',
		)
	),
);

