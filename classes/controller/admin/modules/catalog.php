<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Admin_Modules_Catalog extends Controller_Admin_Front {

	public $inner_layout = 'layout/inner';

	protected $top_menu_item = 'modules';
	protected $sub_title = 'Catalog';
	protected $category_id;

	private $not_deleted_categories = array();

	public function before()
	{
		parent::before();
		$this->category_id = (int) Request::current()->query('category');
		$this->template
			->set_global('CATALOG_CATEGORY_ID', $this->category_id);
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

	public function action_index()
	{
		$orm = ORM::factory('catalog_Category')
			->where('category_id', '=', $this->category_id);
		
		$paginator_orm = clone $orm;
		$paginator = new Paginator('admin/layout/paginator');
		$paginator
			->per_page(20)
			->count($paginator_orm->count_all());
		unset($paginator_orm);
		
		$categories = $orm
			->paginator($paginator)
			->find_all();
		
		$acl_edit = $this->acl->is_allowed($this->user, $orm, 'edit');
			
		$this->left_menu_category_list();
		if ($acl_edit) {
			$this->left_menu_category_add();
		}
		
		if ($this->category_id) {
			$category_orm = ORM::factory('catalog_Category', $this->category_id);
			if ( ! $category_orm->loaded()) {
				throw new HTTP_Exception_404();
			}
			$this->left_menu_category_elements_list($category_orm->id);
			$this->title = $category_orm->title;
		} else {
			$this->title = __('Catalog');
		}
		
		if ($acl_edit) {
			$this->left_menu_category_fix();
		}
		
		$this->sub_title = __('Categories');
			
		$this->template
			->set_filename('modules/catalog/category/list')
			->set('list', $categories)
			->set('paginator', $paginator)
			->set('breadcrumbs', $this->_get_breadcrumbs($this->category_id))
			->set('not_deleted_categories', $this->not_deleted_categories);
	}
	
	private function _get_breadcrumbs($key)
	{
		$categories = ORM::factory('catalog_Category')
			->order_by('category_id', 'asc')
			->order_by('position', 'asc')
			->find_all()
			->as_array('id');
		
		$link_tpl = Route::url('modules', array(
			'controller' => 'catalog',
			'query'      => Helper_Page::make_query_string(array(
				'category' => '--CATEGORY_ID--'
			)),
		));
			
		$breadcrumbs = array();
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
			'link' => Route::url('modules', array(
				'controller' => 'catalog',
			)),
			'icon' => TRUE,
		);
		
		return array_reverse($breadcrumbs);
	}
	
	public function action_category_edit()
	{
		$id = (int) Request::current()->param('id');
		$helper_orm = ORM_Helper::factory('catalog_Category');
		$orm = $helper_orm->orm();
		if ( (bool) $id) {
			$orm
				->and_where('id', '=', $id)
				->find();
			if ( ! $orm->loaded() OR ! $this->acl->is_allowed($this->user, $orm, 'edit')) {
				throw new HTTP_Exception_404();
			}
			$this->title = __('Edit category');
		} else {
			$this->title = __('Add category');
		}
		
		$query_array = array(
			'category' => $this->category_id
		);
		$p = Request::current()->query(Paginator::QUERY_PARAM);
		if ( (bool) $id && ! empty($p)) {
			$query_array[ Paginator::QUERY_PARAM ] = $p;
		}
		$list_url = Route::url('modules', array(
			'controller' => 'catalog',
			'query'      => Helper_Page::make_query_string($query_array),
		));
		
		if ($this->is_cancel) {
			Request::$current->redirect($list_url);
		}
	
		$errors = array();
		$submit = Request::$current->post('submit');
		if ($submit) {
			try {
				if ( (bool) $id) {
					$orm->updater_id = $this->user->id;
					$orm->updated = date('Y-m-d H:i:s');
				} else {
					$orm->creator_id = $this->user->id;
				}
	
				$values = $orm->check_meta_fields(
					Request::$current->post(),
					'meta_tags'
				);
				if (empty($values['uri']) OR $this->row_exist($orm, 'uri', $values['uri'])) {
					$values['uri'] = $this->unique_transliterate($values['title'], $orm, 'uri');
				}
	
				$helper_orm->save($values + $_FILES);
				Controller_Admin_Structure::clear_structure_cache();
			} catch (ORM_Validation_Exception $e) {
				$errors = $e->errors('');
			}
		}
	
		if ( ! empty($errors) OR $submit != 'save_and_exit') {
			$categories = array(
				0 => __('-- Root category --')
			) + $this->_get_categories_list();
			
			$this->left_menu_category_list();
			if ($this->acl->is_allowed($this->user, $orm, 'edit')) {
				$this->left_menu_category_add();
			}
			if ( (bool) $id) {
				$this->left_menu_category_elements_list($id);
			}
			$this->template
				->set_filename('modules/catalog/category/edit')
				->set('errors', $errors)
				->set('helper_orm', $helper_orm)
				->set('categories', $categories)
				->set('not_deleted_categories', $this->not_deleted_categories);
		} else {
			Request::current()->redirect($list_url);
		}
	}
	
	
	public function action_category_delete()
	{
		$id = (int) Request::current()->param('id');
	
		$helper_orm = ORM_Helper::factory('catalog_Category');
		$orm = $helper_orm->orm();
		$orm
			->and_where('id', '=', $id)
			->find();
	
		if ( ! $orm->loaded() OR ! $this->acl->is_allowed($this->user, $orm, 'edit')) {
			throw new HTTP_Exception_404();
		}
		if (in_array($orm->id, $this->not_deleted_categories)) {
			throw new HTTP_Exception_404();
		}
	
		if ($this->delete_element($helper_orm)) {
			Controller_Admin_Structure::clear_structure_cache();
				
			$list_url = Route::url('modules', array(
				'controller' => 'catalog',
				'query'      => Helper_Page::make_query_string(array(
					'category' => $this->category_id
				)),
			));
			Request::current()->redirect($list_url);
		}
	}
	
	public function action_category_position()
	{
		$id = (int) Request::current()->param('id');
		$mode = Request::current()->query('mode');
		$errors = array();
		$helper_orm = ORM_Helper::factory('catalog_Category');
		$orm = $helper_orm->orm();
	
		try {
			if ($mode !== 'fix') {
				$orm
					->where('category_id', '=', $this->category_id)
					->and_where('id', '=', $id)
					->find();
				
				if ( ! $orm->loaded() OR ! $this->acl->is_allowed($this->user, $orm, 'edit')) {
					throw new HTTP_Exception_404();
				}
	
				switch ($mode) {
					case 'up':
						$helper_orm
							->position_move('position', ORM_Position::MOVE_PREV);
						break;
					case 'down':
						$helper_orm
							->position_move('position', ORM_Position::MOVE_NEXT);
						break;
					case 'first':
						$helper_orm
							->position_first('position');
						break;
					case 'last':
						$helper_orm
							->position_last('position');
						break;
				}
			} else {
				if ($this->acl->is_allowed($this->user, $orm, 'fix_positions')) {
					$helper_orm
						->position_fix('position');
				}
			}
	
			Controller_Admin_Structure::clear_structure_cache();
		} catch (ORM_Validation_Exception $e) {
			$errors = $e->errors('');
			$this->template
				->set_filename('layout/error')
				->set('errors', $errors)
				->set('title', __('Error'));
		}
	
		if (empty($errors)) {
				
			if ($mode != 'fix') {
				$query_array = array();
				if ($this->category_id > 0) {
					$query_array['category'] = $this->category_id;
				}
				$p = Request::current()->query( Paginator::QUERY_PARAM );
				if ( ! empty($p)) {
					$query_array[ Paginator::QUERY_PARAM ] = $p;
				}
				$list_url = Route::url('modules', array(
					'controller' => 'catalog',
					'id'         => ($this->category_id > 0 ? $this->category_id : NULL),
					'query'      => Helper_Page::make_query_string($query_array),
				));
			} else {
				$list_url = Route::url('modules', array(
					'controller' => 'catalog',
				));
			}
				
			Request::current()->redirect($list_url);
		}
	}
	

	public function action_category()
	{
		$this->category_id = $id = (int) Request::current()->param('id');
		$category_orm = ORM::factory('catalog_Category')
			->and_where('id', '=', $this->category_id)
			->find();
		if ( ! $category_orm->loaded()) {
			throw new HTTP_Exception_404();
		}
		
		$orm = ORM::factory('catalog')
			->where('category_id', '=', $this->category_id);
		
		$paginator_orm = clone $orm;
		$paginator = new Paginator('admin/layout/paginator');
		$paginator
			->per_page(20)
			->count($paginator_orm->count_all());
		unset($paginator_orm);

		$list = $orm
			->paginator($paginator)
			->find_all();

		$this->title = $category_orm->title;
		$this->sub_title = __('Elements list');
		$this->left_menu_category_list();
		if ($this->acl->is_allowed($this->user, $category_orm, 'edit')) {
			$this->left_menu_category_add();
		}
		$this->left_menu_category_elements_list($this->category_id);
		$this->left_menu_element_add();
		
		$this->template
			->set_filename('modules/catalog/element/list')
			->set('list', $list)
			->set('breadcrumbs', $this->_get_breadcrumbs($this->category_id))
			->set('paginator', $paginator);
	}

	public function action_edit()
	{
		$category_orm = ORM::factory('catalog_Category')
			->and_where('id', '=', $this->category_id)
			->find();
		if ( ! $category_orm->loaded()) {
			throw new HTTP_Exception_404();
		}
		
		$id = (int) Request::current()->param('id');
		$helper_orm = ORM_Helper::factory('catalog');
		$orm = $helper_orm->orm();
		if ( (bool) $id) {
			$orm
				->where('id', '=', $id)
				->find();

			if ( ! $orm->loaded() OR ! $this->acl->is_allowed($this->user, $orm, 'edit')) {
				throw new HTTP_Exception_404();
			}
			$this->title = __('Edit element');
		} else {
			$this->title = __('Add element');
		}
		
		$query_array = array(
			'category' => $this->category_id,
		);
		$p = Request::current()->query( Paginator::QUERY_PARAM );
		if ( (bool) $id && ! empty($p)) {
			$query_array[ Paginator::QUERY_PARAM ] = $p;
		}
		$list_url = Route::url( 'modules', array(
			'controller' => 'catalog',
			'action'     => 'category',
			'id'         => $this->category_id,
			'query'      => Helper_Page::make_query_string($query_array),
		));

		if ($this->is_cancel) {
			Request::current()->redirect($list_url);
		}
		
		if (empty($orm->sort)) {
			$orm->sort = 500;
		}
		
		$errors = array();
		$submit = Request::$current->post('submit');
		if ($submit) {
			try {
				if ( (bool) $id) {
					$orm->updater_id = $this->user->id;
					$orm->updated = date('Y-m-d H:i:s');
				} else {
					$orm->creator_id = $this->user->id;
					$orm->category_id = $this->category_id;
				}

				$values = $orm->check_meta_fields(
					Request::current()->post(),
					'meta_tags'
				);
				
				$helper_orm->save($values + $_FILES);
			} catch (ORM_Validation_Exception $e) {
				$errors = $e->errors( '' );
				if ( ! empty($errors['_files'])) {
					$errors = array_merge($errors, $errors['_files']);
					unset($errors['_files']);
				}
			}
		}

		if ( ! empty($errors) OR $submit != 'save_and_exit') {
			$categories = array(
				0 => __('-- Root category --')
			) + $this->_get_categories_list();
			
			$this->left_menu_category_list();
			if ($this->acl->is_allowed($this->user, $category_orm, 'edit')) {
				$this->left_menu_category_add();
			}
			$this->left_menu_category_elements_list($category_orm->id);
			if ($this->acl->is_allowed($this->user, $orm, 'edit')) {
				$this->left_menu_element_add();
			}
			
			$this->template
				->set_filename('modules/catalog/element/edit')
				->set('errors', $errors)
				->set('helper_orm', $helper_orm)
				->set('categories', $categories);
		} else {
			Request::current()->redirect($list_url);
		}
	}
	
	public function action_delete()
	{
		$id = (int) Request::current()->param('id');
	
		$helper_orm = ORM_Helper::factory('catalog');
		$orm = $helper_orm->orm();
		$orm
			->and_where('id', '=', $id)
			->find();
	
		if ( ! $orm->loaded() OR ! $this->acl->is_allowed($this->user, $orm, 'edit')) {
			throw new HTTP_Exception_404();
		}
	
		if ($this->delete_element($helper_orm)) {
			$list_url = Route::url('modules', array(
				'controller' => 'catalog',
				'action'     => 'category',
				'id'         => $this->category_id,
				'query'      => Helper_Page::make_query_string(array(
					'category' => $this->category_id
				)),
			));
			Request::current()->redirect($list_url);
		}
	}
	
	public function action_dyn_sort()
	{
		$this->auto_render = FALSE;
		
		$id = (int) $this->request->post('id');
		$field = $this->request->post('field');
		$value = $this->request->post('value');
		
		$orm = ORM::factory('catalog', $id);
		if (empty($field) OR ! $orm->loaded() OR ! $this->acl->is_allowed($this->user, $orm, 'edit')) {
			throw new HTTP_Exception_404();
		}
		try {
			$orm->values(array(
				$field => $value
			))->save();
		} catch (ORM_Validation_Exception $e) {
			throw new HTTP_Exception_404();
		}
		
		Ku_AJAX::send('json', $orm->$field);
	}

	private function _get_categories_list()
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
	
	private function _print_list($categories) {
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
	
	private function left_menu_category_add()
	{
		$this->_ex_menu_items = array_merge_recursive($this->_ex_menu_items, array(
			'catalog' => array(
				'sub' => array(
					'add_category' => array(
						'title'    => __('Add category'),
						'link'     => Route::url('modules', array(
							'controller' => 'catalog',
							'action'     => 'category_edit',
							'query'      => 'category={CATEGORY_ID}'
						)),
					),
				),
			),
		));
	}
	
	private function left_menu_category_list()
	{
		$this->_ex_menu_items = array_merge_recursive($this->_ex_menu_items, array(
			'catalog' => array(
				'sub' => array(
					'list_category' => array(
						'title'    => __('Categories list'),
						'link'     => Route::url('modules', array(
							'controller' => 'catalog',
							'query'      => 'category={CATEGORY_ID}'
						)),
					),
				),
			),
		));
	}
	
	private function left_menu_category_elements_list($category_id)
	{
		$this->_ex_menu_items = array_merge_recursive($this->_ex_menu_items, array(
			'catalog_elements' => array(
				'title'    => __('Elements list'),
				'link'     => Route::url('modules', array(
					'controller' => 'catalog',
					'action'     => 'category',
					'id'         => $category_id,
					'query'      => 'category={CATEGORY_ID}'
				)),
				'sub' => array(),
			),
		));
	}
	
	private function left_menu_element_add()
	{
		$this->_ex_menu_items = array_merge_recursive($this->_ex_menu_items, array(
			'catalog_elements' => array(
				'sub' => array(
					'add_element' => array(
						'title'   => __('Add element'),
						'link'    => Route::url('modules', array(
							'controller' => 'catalog',
							'action'     => 'edit',
							'query'      => 'category={CATEGORY_ID}'
						)),
					),
				),
			),
		));
	}
	
	private function left_menu_category_fix()
	{
		$this->_ex_menu_items = array_merge_recursive($this->_ex_menu_items, array(
			'fix' => array(
				'title' => __('Fix positions'),
				'link'  => Route::url('modules', array(
					'controller' => 'catalog',
					'action'     => 'category_position',
					'query'      => 'mode=fix',
				)),
			),
		));
	}
} 
