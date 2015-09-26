<?php defined('SYSPATH') or die('No direct script access.');

class Helper_Catalog {
	
	public static function route($uri, $route)
	{
		$params = $route->defaults();
		$route_name = Route::name($route);
		$_d = strpos($route_name, '<->');
		if ($_d === FALSE) {
			return $params;
		}
		
		$page_id = (int) substr($route_name, 0, $_d);
		$url_base = Page_Route::dynamic_base_uri($page_id);
		$uri = substr($uri, strlen($url_base));
		$uri = trim($uri, '/');
		$sergments = explode('/', $uri);
		if (count($sergments) == 0) {
			return $params;
		}
		
		$_seg = array_pop($sergments);
		while ($_seg) {
			if (substr($_seg, -5) == '.html') {
				$params['element_uri'][] = substr($_seg, 0, -5);
			} else {
				$params['category_uri'][] = $_seg;
			}
			
			$_seg = array_pop($sergments);
		}

		return $params;
	}
	
}