<?php defined('SYSPATH') or die('No direct access allowed.');

	$orm = $helper_orm->orm();
	$labels = $orm->labels();
	$required = $orm->required_fields();
	
/**** active ****/
	
	echo View_Admin::factory('form/control', array(
		'field'    => 'active',
		'errors'   => $errors,
		'labels'   => $labels,
		'required' => $required,
		'controls' => Form::hidden('active', '').Form::checkbox('active', '1', (bool) $orm->active, array(
			'id' => 'active_field',
		)),
	));
	
/**** category_id ****/
	
	echo View_Admin::factory('form/control', array(
		'field'    => 'category_id',
		'errors'   => $errors,
		'labels'   => $labels,
		'required' => $required,
		'controls' => Form::select('category_id', $categories, ($orm->category_id === NULL ? $CATALOG_CATEGORY_ID : $orm->category_id), array(
			'id'      => 'category_id_field',
			'class'   => 'input-xlarge',
		)),
	));
	
/**** code ****/
	
	echo View_Admin::factory('form/control', array(
		'field'		=>	'code',
		'errors'	=>	$errors,
		'labels'	=>	$labels,
		'required'	=>	$required,
		'controls'	=>	Form::input('code', $orm->code, array(
			'id'       => 'code_field',
			'class'    => 'input-xlarge',
		)),
	));
	
/**** title ****/
	
	echo View_Admin::factory('form/control', array(
		'field'		=>	'title',
		'errors'	=>	$errors,
		'labels'	=>	$labels,
		'required'	=>	$required,
		'controls'	=>	Form::input('title', $orm->title, array(
			'id'       => 'title_field',
			'class'    => 'input-xlarge',
		)),
	));
	
/**** sort ****/
	
	echo View_Admin::factory('form/control', array(
		'field'		=>	'sort',
		'errors'	=>	$errors,
		'labels'	=>	$labels,
		'required'	=>	$required,
		'controls'	=>	Form::input('sort', $orm->sort, array(
			'id'       => 'sort_field',
			'class'    => 'input-xlarge',
		)),
	));
	
/**** additional params block ****/
	
	echo View_Admin::factory('form/seo', array(
		'item'		=>	$orm,
		'errors'	=>	$errors,
		'labels'	=>	$labels,
		'required'	=>	$required,
	));
	