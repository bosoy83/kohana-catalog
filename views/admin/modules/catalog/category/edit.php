<?php defined('SYSPATH') or die('No direct access allowed.');

	$orm = $helper_orm->orm();
	$labels = $orm->labels();
	$required = $orm->required_fields();

	$query_array = array(
		'category' => $CATALOG_CATEGORY_ID
	);
	if ( ! empty($BACK_URL)) {
		$query_array['back_url'] = $BACK_URL;
	}
	if ($orm->loaded()) {
		$p = Request::current()->query( Paginator::QUERY_PARAM );
		if ( ! empty($p)) {
			$query_array[ Paginator::QUERY_PARAM ] = $p;
		}
		$action = Route::url('modules', array(
			'controller' => 'catalog_category',
			'action' => 'edit',
			'id' => $orm->id,
			'query' => Helper_Page::make_query_string($query_array),
		));
	} else {
		$action = Route::url('modules', array(
			'controller' => 'catalog_category',
			'action' => 'edit',
			'query' => Helper_Page::make_query_string($query_array),
		));
	}

	echo View_Admin::factory('layout/error')
		->set('errors', $errors);
?>
	<form method="post" action="<?php echo $action; ?>" enctype="multipart/form-data" class="form-horizontal kr-form-horizontal" >
		<div class="tabbable">
			<ul class="nav nav-tabs kr-nav-tsbs">
<?php
				echo '<li class="active">', HTML::anchor('#tab-main', __('Main'), array(
					'data-toggle' => 'tab'
				)), '</li>'; 
				echo '<li>', HTML::anchor('#tab-description', __('Description'), array(
					'data-toggle' => 'tab'
				)), '</li>'; 
?>
			</ul>
			<div class="tab-content kr-tab-content">
				<div class="tab-pane kr-tab-pane active" id="tab-main">
<?php
					echo View_Admin::factory('modules/catalog/category/tab/main', array(
						'helper_orm' => $helper_orm,
						'errors' => $errors,
						'categories' => $categories,
					)); 
?>
				</div>
				<div class="tab-pane kr-tab-pane" id="tab-description">
<?php
					echo View_Admin::factory('modules/catalog/category/tab/description', array(
						'helper_orm' => $helper_orm,
						'errors' => $errors,
					)); 
?>
				</div>
			</div>
		</div>
<?php
		echo View_Admin::factory('layout/submit_buttons');
?>		
	</form>
