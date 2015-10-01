<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Admin_Modules_Catalog_Category extends Controller_Admin_Modules_Catalog {

	private $not_deleted_categories = array();
	
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
		
		if ($this->is_initial) {
			$this->left_menu_category_list();
			$acl_edit = $this->acl->is_allowed($this->user, $orm, 'edit');
			if ($acl_edit) {
				$this->left_menu_category_add();
			}
		}
		
		if ($this->category_id) {
			$category_orm = ORM::factory('catalog_Category', $this->category_id);
			if ( ! $category_orm->loaded()) {
				throw new HTTP_Exception_404();
			}
			$this->title = $category_orm->title;
			
			if ($this->is_initial) {
				$this->left_menu_element_list($category_orm->id);
			}
		} else {
			$this->title = __('Catalog');
		}
		$this->sub_title = __('Categories');
		
		if ($this->is_initial AND $acl_edit) {
			$this->left_menu_category_fix();
		}
			
		$this->template
			->set_filename('modules/catalog/category/list')
			->set('list', $categories)
			->set('paginator', $paginator)
			->set('breadcrumbs', $this->_get_breadcrumbs($this->category_id))
			->set('not_deleted_categories', $this->not_deleted_categories);
	}
	
	public function action_edit()
	{
		$requset = $this->request->current();
		$id = (int) $this->request->current()->param('id');
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
		
		if (empty($this->back_url)) {
			$query_array = array(
				'category' => $this->category_id
			);
			$p = $this->request->current()->query(Paginator::QUERY_PARAM);
			if ( (bool) $id && ! empty($p)) {
				$query_array[ Paginator::QUERY_PARAM ] = $p;
			}
			$this->back_url = Route::url('modules', array(
				'controller' => $this->_controller_name['category'],
				'query' => Helper_Page::make_query_string($query_array),
			));
		}
		
		if ($this->is_cancel) {
			$requset->redirect($this->back_url);
		}
	
		$errors = array();
		$submit = $this->request->current()->post('submit');
		if ($submit) {
			try {
				if ( (bool) $id) {
					$orm->updater_id = $this->user->id;
					$orm->updated = date('Y-m-d H:i:s');
				} else {
					$orm->creator_id = $this->user->id;
				}
	
				$values = $orm->check_meta_fields(
					$this->request->current()->post(),
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
			
			if ($this->is_initial) {
				$this->left_menu_category_list();
				if ($this->acl->is_allowed($this->user, $orm, 'edit')) {
					$this->left_menu_category_add();
				}
				if ( (bool) $id) {
					$this->left_menu_element_list($id);
				}
			}
			$this->template
				->set_filename('modules/catalog/category/edit')
				->set('errors', $errors)
				->set('helper_orm', $helper_orm)
				->set('categories', $categories)
				->set('not_deleted_categories', $this->not_deleted_categories);
		} else {
			$requset->redirect($this->back_url);
		}
	}
	
	
	public function action_delete()
	{
		$id = (int) $this->request->current()->param('id');
	
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
				
			if (empty($this->back_url)) {
				$query_array = array(
					'category' => $this->category_id
				);
				$list_url = Route::url('modules', array(
					'controller' => $this->_controller_name['category'],
					'query' => Helper_Page::make_query_string($query_array),
				));
			}
			$this->request->current()
				->redirect($this->back_url);
		}
	}
	
	public function action_position()
	{
		$id = (int) $this->request->current()->param('id');
		$mode = $this->request->current()->query('mode');
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
				
			if (empty($this->back_url)) {
				$query_array = array(
					'category' => $this->category_id
				);
	
				if ($mode != 'fix') {
					$p = $this->request->current()->query( Paginator::QUERY_PARAM );
					if ( ! empty($p)) {
						$query_array[ Paginator::QUERY_PARAM ] = $p;
					}
					$this->back_url = Route::url('modules', array(
						'controller' => $this->_controller_name['category'],
						'query' => Helper_Page::make_query_string($query_array),
					));
				} else {
					$this->back_url = Route::url('modules', array(
						'controller' => $this->_controller_name['category'],
						'query' => Helper_Page::make_query_string($query_array),
					));
				}
			}
				
			$this->request->current()
				->redirect($this->back_url);
		}
	}
	
} 
