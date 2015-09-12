<?php defined('SYSPATH') or die('No direct access allowed.');

return array(
	'left_menu' => array(
		'catalog'    => array(
			'title'  => __('Catalog'),
			'link'   => Route::url('modules', array(
				'controller' => 'catalog',
			)),
			'sub'    => array(),
		),
	),
	'a2' => array(
		'resources' => array(
			'catalog_controller' => 'module_controller',
			'catalog_category'   => 'module',
			'catalog'            => 'module',
		),
		'rules' => array(
			'allow' => array(
				'controller_access' => array(
					'role'      => 'main',
					'resource'  => 'catalog_controller',
					'privilege' => 'access',
				),
				'catalog_category_edit' => array(
					'role'      => 'main',
					'resource'  => 'catalog_category',
					'privilege' => 'edit',
				),
				'catalog_edit' => array(
					'role'      => 'main',
					'resource'  => 'catalog',
					'privilege' => 'edit',
				),
				
				'catalog_category_fix' => array(
					'role'      => 'main',
					'resource'  => 'catalog_category',
					'privilege' => 'fix_positions',
				),
				'catalog_fix' => array(
					'role'      => 'main',
					'resource'  => 'catalog',
					'privilege' => 'fix_positions',
				),
			),
		)
	),
);