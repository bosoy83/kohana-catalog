<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Admin_Modules_Catalog extends Controller_Admin_Front {

	protected $top_menu_item = 'modules';
	protected $sub_title = 'Catalog';
	protected $category_id;
	protected $module_config = 'catalog';
	protected $_controller_name = array(
		'category' => 'catalog_category',
		'element' => 'catalog_element',
	);
	
	public function before()
	{
		parent::before();
		$this->category_id = (int) Request::current()->query('category');
		$this->template
			->bind_global('CATALOG_CATEGORY_ID', $this->category_id);
		
		$query_controller = $this->request->query('controller');
		if ( ! empty($query_controller) AND is_array($query_controller)) {
			$this->_controller_name = $this->request->query('controller');
		}
		$this->template
			->bind_global('CONTROLLER_NAME', $this->_controller_name);
	}

	protected function get_aside_view()
	{
		$menu_items = array_merge_recursive(
			$this->module_config->get('left_menu'),
			$this->_ex_menu_items
		);
		
		return parent::get_aside_view()
			->set('menu_items', $menu_items)
			->set('replace', array(
				'{CATEGORY_ID}' =>	$this->category_id,
			));
	}

	protected function _get_breadcrumbs($key, $for_elements = FALSE)
	{
		$categories = ORM::factory('catalog_Category')
			->order_by('category_id', 'asc')
			->order_by('position', 'asc')
			->find_all()
			->as_array('id');
		
		$query_array = array(
			'category' => '--CATEGORY_ID--'
		);
		$link_tpl = Route::url('modules', array(
			'controller' => $this->_controller_name['category'],
			'query' => Helper_Page::make_query_string($query_array),
		));
			
		$breadcrumbs = array();
		if ($for_elements) {
			$breadcrumbs[] = array(
				'title' => __('Elements list'),
				'link' => $this->request->current()->url(),
			);
		}
		
		$_category = Arr::get($categories, $key);
		while ($_category) {
			$breadcrumbs[] = array(
				'title' => $_category->title,
				'link' => str_replace('--CATEGORY_ID--', $_category->id, $link_tpl),
			);
			
			$_key = $_category->category_id;
			$_category = Arr::get($categories, $_key);
		}
		
		$breadcrumbs[] = array(
			'title' => __('Catalog'),
			'link' => str_replace('--CATEGORY_ID--', 0, $link_tpl),
			'icon' => TRUE,
		);
		
		return array_reverse($breadcrumbs);
	}
	
	protected function _get_categories_list()
	{
		$categories_db = ORM::factory('catalog_Category')
			->order_by('category_id', 'asc')
			->order_by('position', 'asc')
			->find_all();
		
		$categories = array();
		foreach ($categories_db as $_item) {
			$_key = $_item->id;
			if ($_item->category_id == 0) {
				$categories[$_key] = array(
					'id' => $_key,
					'title' => $_item->title,
					'level' => 0,
					'children' => array(),
				);
			} elseif (array_key_exists($_item->category_id, $categories)) {
				$_parent = & $categories[$_item->category_id];
				
				$_parent['children'][$_key] = array(
					'id' => $_key,
					'title' => $_item->title,
					'level' => $_parent['level'] + 1,
					'children' => array(),
				);
				unset($_parent);
			}
		}
			
		return $this->_print_list($categories);
	}
	
	protected function _print_list($categories) {
		$return = array();
		foreach ($categories as $item) {
			$_title = str_repeat('&mdash;', $item['level']).' '.$item['title'];
			$return[$item['id']] = trim($_title);
			if ( ! empty($item['children'])) {
				$return = $return + $this->_print_list($item['children']);
			}
		}
		return $return;
	}
	
	protected function left_menu_category_list()
	{
		$this->_ex_menu_items = array_merge_recursive($this->_ex_menu_items, array(
			'catalog' => array(
				'sub' => array(
					'list_category' => array(
						'title' => __('Categories list'),
						'link' => Route::url('modules', array(
							'controller' => $this->_controller_name['category'],
							'query' => 'category={CATEGORY_ID}'
						)),
					),
				),
			),
		));
	}
	
	protected function left_menu_category_add()
	{
		$this->_ex_menu_items = array_merge_recursive($this->_ex_menu_items, array(
			'catalog' => array(
				'sub' => array(
					'add_category' => array(
						'title' => __('Add category'),
						'link' => Route::url('modules', array(
							'controller' => $this->_controller_name['category'],
							'action' => 'edit',
							'query' => 'category={CATEGORY_ID}'
						)),
					),
				),
			),
		));
	}
	
	protected function left_menu_category_fix()
	{
		$this->_ex_menu_items = array_merge_recursive($this->_ex_menu_items, array(
			'fix' => array(
				'title' => __('Fix positions'),
				'link'  => Route::url('modules', array(
					'controller' => $this->_controller_name['category'],
					'action' => 'position',
					'query' => 'mode=fix',
				)),
			),
		));
	}
	
	protected function left_menu_element_list()
	{
		$this->_ex_menu_items = array_merge_recursive($this->_ex_menu_items, array(
			'catalog_elements' => array(
				'title' => __('Elements list'),
				'link' => Route::url('modules', array(
					'controller' => $this->_controller_name['element'],
					'query' => 'category={CATEGORY_ID}'
				)),
				'sub' => array(),
			),
		));
	}
	
	protected function left_menu_element_add()
	{
		$this->_ex_menu_items = array_merge_recursive($this->_ex_menu_items, array(
			'catalog_elements' => array(
				'sub' => array(
					'add_element' => array(
						'title' => __('Add element'),
						'link' => Route::url('modules', array(
							'controller' => $this->_controller_name['element'],
							'action' => 'edit',
							'query' => 'category={CATEGORY_ID}'
						)),
					),
				),
			),
		));
	}
	
} 
