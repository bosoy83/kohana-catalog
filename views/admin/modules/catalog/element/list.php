<?php defined('SYSPATH') or die('No direct access allowed.');

	echo View_Admin::factory('layout/breadcrumbs', array(
		'breadcrumbs' => $breadcrumbs
	));
	
	if ($list->count() > 0 ): 
		$dyn_sort_action = Route::url('modules', array(
			'controller' => 'catalog_element',
			'action' => 'dyn_sort',
		));
	
		$query_array = array(
			'category' => $CATALOG_CATEGORY_ID,
		);
		if ( ! empty($BACK_URL)) {
			$query_array['back_url'] = $BACK_URL;
		}
		$delete_tpl = Route::url('modules', array(
			'controller' => $CONTROLLER_NAME['element'],
			'action' => 'delete',
			'id' => '{id}',
			'query' => Helper_Page::make_query_string($query_array),
		));
		unset($query_array['back_url']);

		$p = Request::current()->query( Paginator::QUERY_PARAM );
		if ( ! empty($p)) {
			$query_array[ Paginator::QUERY_PARAM ] = $p;
		}
		$edit_tpl = Route::url('modules', array(
			'controller' => $CONTROLLER_NAME['element'],
			'action' => 'edit',
			'id' => '{id}',
			'query' => Helper_Page::make_query_string($query_array),
		));
?>
		<table class="table table-bordered table-striped">
			<colgroup>
				<col class="span1">
				<col class="span2">
				<col class="span3">
				<col class="span1">
				<col class="span2">
			</colgroup>
			<thead>
				<tr>
					<th><?php echo __('ID'); ?></th>
					<th><?php echo __('Image'); ?></th>
					<th><?php echo __('Title'); ?></th>
					<th><?php echo __('Sort'); ?></th>
					<th><?php echo __('Actions'); ?></th>
				</tr>
			</thead>
			<tbody>
<?php 
			$orm_helper = ORM_Helper::factory('catalog_Element');
			foreach ($list as $_orm):
?>
			<tr>
				<td><?php echo $_orm->id ?></td>
				<td>
<?php
				if ($_orm->image) {
					$img_size = getimagesize(DOCROOT.$orm_helper->file_path('image', $_orm->image));
					
					if ($img_size[0] > 100 OR $img_size[1] > 100) {
						$thumb = Thumb::uri('admin_image_100', $orm_helper->file_uri('image', $_orm->image));
					} else {
						$thumb = $orm_helper->file_uri('image', $_orm->image);
					}
					
					if ($img_size[0] > 300 OR $img_size[1] > 300) {
						$flyout = Thumb::uri('admin_image_300', $orm_helper->file_uri('image', $_orm->image));
					} else {
						$flyout = $orm_helper->file_uri('image', $_orm->image);
					}
					
					echo HTML::anchor($flyout, HTML::image($thumb, array(
						'title' => ''
					)), array(
						'class' => 'js-photo-gallery',
					));
				} else {
					echo __('No image');
				}
?>				
				</td>
				<td>
<?php
					if ( (bool) $_orm->active) {
						echo '<i class="icon-eye-open"></i>&nbsp;';
					} else {
						echo '<i class="icon-eye-open" style="background: none;"></i>&nbsp;';
					}
					echo HTML::chars($_orm->title)
?>
				</td>
				<td class="js-dyn-input" data-action="<?php echo HTML::chars($dyn_sort_action); ?>" data-id="<?php echo $_orm->id ?>" data-field="sort">
<?php 
					echo $_orm->sort; 
?>
				</td>
				<td>
<?php
				if ($ACL->is_allowed($USER, $_orm, 'edit')) {
					
					echo '<div class="btn-group">';
					
						echo HTML::anchor(str_replace('{id}', $_orm->id, $edit_tpl), '<i class="icon-edit"></i> '.__('Edit'), array(
							'class' => 'btn',
							'title' => __('Edit'),
						));
					
						echo '<a class="btn dropdown-toggle" data-toggle="dropdown" href="#"><span class="caret"></span></a>';
						echo '<ul class="dropdown-menu">';
						
							echo '<li>', HTML::anchor(str_replace('{id}', $_orm->id, $delete_tpl), '<i class="icon-remove"></i> '.__('Delete'), array(
								'class' => 'delete_button',
								'title' => __('Delete'),
							)), '</li>';
							
						echo '</ul>';
					echo '</div>';
					
				}
?>
				</td>
			</tr>
<?php 
		endforeach;
?>
		</tbody>
	</table>
<?php
	$query_array = array(
		'category' => $CATALOG_CATEGORY_ID,
	);
	if ( ! empty($BACK_URL)) {
		$query_array['back_url'] = $BACK_URL;
	}
	$link = Route::url('modules', array(
		'controller' => $CONTROLLER_NAME['element'],
		'action' => 'category',
		'id' => $CATALOG_CATEGORY_ID,
		'query' => Helper_Page::make_query_string($query_array),
	));

	echo $paginator->render($link);
endif;
