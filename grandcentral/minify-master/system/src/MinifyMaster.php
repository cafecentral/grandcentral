<?php 

class MinifyMaster{

	public function __construct()
	{
		if(((defined('SITE_MINIFY') && SITE_MINIFY === true) || !defined('SITE_MINIFY'))
			&& (defined('SITE_DEBUG') && SITE_DEBUG === false))
		{
			$minifier_css = new MinifyCSS();
			if ($minifier_css->last_modif_folder('css'))
				$minifier_css->minify_resources('css', $minifier_css);
			master::clean_bind('css');

			$minifier_js = new MinifyJS();
			if ($minifier_css->last_modif_folder('script'))
				$minifier_js->minify_resources('script', $minifier_js);
		    master::clean_bind('script');

		    $current_page = defined('item') ? i('page', current)['url']->get().i(item, current)['url']->get() : i('page', current)['url']->get();
		    $app = app('cache');
		    $app->bind_script('../cache/mini/'.md5($current_page).'.js');
	    	$app->bind_css('../cache/mini/'.md5($current_page).'.css');
		}
	}
}

?>