<?php defined('SYSPATH') or die('No direct script access.');

class Model_Catalog extends ORM_Base {

	protected $_table_name = 'catalog';
	protected $_sorting = array('sort' => 'ASC', 'title' => 'ASC');
	protected $_deleted_column = 'delete_bit';
	protected $_active_column = 'active';

	protected $_belongs_to = array(
		'category' => array(
			'model'       => 'catalog_category',
			'foreign_key' => 'category_id',
		),
	);
	
	public function labels()
	{
		return array(
			'category_id'     => 'Category',
			'code'            => 'Article',
			'title'           => 'Title',
			'image'           => 'Image',
			'text'            => 'Text',
			'active'          => 'Active',
			'sort'            => 'Sort',
			'title_tag'       => 'Title tag',
			'keywords_tag'    => 'Keywords tag',
			'description_tag' => 'Desription tag',
		);
	}

	public function rules()
	{
		return array(
			'id' => array(
				array('digit'),
			),
			'category_id' => array(
				array('not_empty'),
				array('digit'),
			),
			'code' => array(
				array('max_length', array(':value', 255)),
			),
			'title' => array(
				array('not_empty'),
				array('max_length', array(':value', 255)),
			),
			'image' => array(
				array('not_empty'),
				array('max_length', array(':value', 255)),
			),
			'sort' => array(
				array('digit'),
			),
			'title_tag' => array(
				array('max_length', array(':value', 255)),
			),
			'keywords_tag' => array(
				array('max_length', array(':value', 255)),
			),
			'description_tag' => array(
				array('max_length', array(':value', 255)),
			),
		);
	}

	public function filters()
	{
		return array(
			TRUE => array(
				array('trim'),
			),
			'title' => array(
				array('strip_tags'),
			),
			'active' => array(
				array(array($this, 'checkbox'))
			),
			'title_tag' => array(
				array('strip_tags'),
			),
			'keywords_tag' => array(
				array('strip_tags'),
			),
			'description_tag' => array(
				array('strip_tags'),
			),
		);
	}
	
}
