<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Admin_Modules_Catalog_Element extends Controller_Admin_Modules_Catalog {

	public function action_index()
	{
		$category_orm = ORM::factory('catalog_Category')
			->and_where('id', '=', $this->category_id)
			->find();
		
		if ( ! $category_orm->loaded()) {
			throw new HTTP_Exception_404();
		}
		
		$orm = ORM::factory('catalog_Element')
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
		
		$this->template
			->set_filename('modules/catalog/element/list')
			->set('list', $list)
			->set('breadcrumbs', $this->_get_breadcrumbs($this->category_id, TRUE))
			->set('paginator', $paginator);
		
		if ($this->is_initial) {
			$this->left_menu_category_list();
			if ($this->acl->is_allowed($this->user, $category_orm, 'edit')) {
				$this->left_menu_category_add();
			}
			$this->left_menu_element_list($this->category_id);
			$this->left_menu_element_add();
		}
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
		$helper_orm = ORM_Helper::factory('catalog_Element');
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
		if ( ! empty($this->back_url)) {
			$query_array['back_url'] = $this->back_url;
		}
		$p = Request::current()->query( Paginator::QUERY_PARAM );
		if ( (bool) $id && ! empty($p)) {
			$query_array[ Paginator::QUERY_PARAM ] = $p;
		}
		$list_url = Route::url( 'modules', array(
			'controller' => 'catalog_element',
			'query' => Helper_Page::make_query_string($query_array),
		));

		if ($this->is_cancel) {
			Request::current()->redirect($list_url);
		}
		
		if (empty($orm->sort)) {
			$orm->sort = 500;
		}
		
		$errors = array();
		$submit = Request::current()->post('submit');
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
				
				if (empty($values['uri']) OR $this->row_exist($orm, 'uri', $values['uri'])) {
					$values['uri'] = $this->unique_transliterate($values['title'], $orm, 'uri');
				}
				
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
			
			if ($this->is_initial) {
				$this->left_menu_category_list();
				if ($this->acl->is_allowed($this->user, $category_orm, 'edit')) {
					$this->left_menu_category_add();
				}
				$this->left_menu_element_list($category_orm->id);
				if ($this->acl->is_allowed($this->user, $orm, 'edit')) {
					$this->left_menu_element_add();
				}
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
	
		$helper_orm = ORM_Helper::factory('catalog_Element');
		$orm = $helper_orm->orm();
		$orm
			->and_where('id', '=', $id)
			->find();
	
		if ( ! $orm->loaded() OR ! $this->acl->is_allowed($this->user, $orm, 'edit')) {
			throw new HTTP_Exception_404();
		}
	
		if ($this->delete_element($helper_orm)) {
			$query_array = array(
				'category' => $this->category_id,
			);
			if ( ! empty($this->back_url)) {
				$query_array['back_url'] = $this->back_url;
			}
			
			$list_url = Route::url('modules', array(
				'controller' => 'catalog_element',
				'query' => Helper_Page::make_query_string($query_array),
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
		
		$orm = ORM::factory('catalog_Element', $id);
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
	
} 
