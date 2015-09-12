<?php defined('SYSPATH') or die('No direct script access.');

class ORM_Helper_Catalog extends ORM_Helper {

	protected $_safe_delete_field = 'delete_bit';
	
	protected $_file_fields = array(
		'image' => array(
			'path' => "upload/images/catalog",
			'uri'  => NULL,
			'on_delete' => ORM_File::ON_DELETE_RENAME,
			'on_update' => ORM_File::ON_UPDATE_RENAME,
			'allowed_src_dirs' => array(),
		),
	);

	public function file_rules()
	{
		return array(
			'image' => array(
				array('Ku_File::valid'),
				array('Ku_File::size', array(':value', '3M')),
				array('Ku_File::type', array(':value', 'jpg, jpeg, bmp, png, gif')),
			),
		);
	}
}
