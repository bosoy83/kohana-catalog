<?php defined('SYSPATH') or die('No direct access allowed.');

return array(
	'left_menu' => array(
		'catalog' => array(
			'title' => __('Catalog'),
			'link' => Route::url('modules', array(
				'controller' => 'catalog_category',
			)),
			'sub' => array(),
		),
	),
	'a2' => array(
		'resources' => array(
			'catalog_category_controller' => 'module_controller',
			'catalog_element_controller' => 'module_controller',
			'catalog_category' => 'module',
			'catalog_element' => 'module',
		),
		'rules' => array(
			'allow' => array(
				'controller_category_access' => array(
					'role' => 'main',
					'resource' => 'catalog_category_controller',
					'privilege' => 'access',
				),
				'catalog_category_edit' => array(
					'role' => 'main',
					'resource' => 'catalog_category',
					'privilege' => 'edit',
				),
				'catalog_category_fix' => array(
					'role' => 'main',
					'resource' => 'catalog_category',
					'privilege' => 'fix_positions',
				),
				
				'controller_element_access' => array(
					'role' => 'main',
					'resource' => 'catalog_element_controller',
					'privilege' => 'access',
				),
				'catalog_element_edit' => array(
					'role' => 'main',
					'resource' => 'catalog_element',
					'privilege' => 'edit',
				),
			),
		)
	),
);