<?php defined('SYSPATH') or die('No direct script access.');

class Model_Catalog_Category extends ORM_Base {

	protected $_table_name = 'catalog_categories';
	protected $_sorting = array('position' => 'ASC');
	protected $_deleted_column = 'delete_bit';
	protected $_active_column = 'active';

	protected $_has_many = array(
		'items' => array(
			'model'       => 'catalog',
			'foreign_key' => 'category_id',
		),
		'categories' => array(
			'model'       => 'catalog_category',
			'foreign_key' => 'category_id',
		),
	);
	
	protected $_belongs_to = array(
		'parent' => array(
			'model'       => 'catalog_category',
			'foreign_key' => 'category_id',
		),
	);

	public function labels()
	{
		return array(
			'category_id'     => 'Category',
			'uri'             => 'URI',
			'title'           => 'Title',
			'image'           => 'Image',
			'text'            => 'Text',
			'active'          => 'Active',
			'position'        => 'Position',
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
			'uri' => array(
				array('min_length', array(':value', 2)),
				array('max_length', array(':value', 100)),
				array('alpha_dash'),
				array(array($this, 'check_uri')),
			),
			'title' => array(
				array('not_empty'),
				array('min_length', array(':value', 2)),
				array('max_length', array(':value', 100)),
			),
			'image' => array(
				array('max_length', array(':value', 255)),
			),
			'position' => array(
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
			'uri' => array(
				array('Ku_Text::slug'),
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
	
	public function check_uri($value)
	{
		$orm = ORM::factory($this->object_name())
			->where('category_id', '=', $this->category_id)
			->where('uri', '=', $value)
			->where('delete_bit', '=', '0');
		
		if ($this->loaded()) {
			$orm->where($this->primary_key(), '!=', $this->pk());
		}
		
		return ( ! $orm->loaded());
	}

}
