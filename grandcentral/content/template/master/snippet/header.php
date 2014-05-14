<?php
/**
 * Description: This is the description of the document.
 * You can add as many lines as you want.
 * Remember you're not coding for yourself. The world needs your doc.
 * Example usage:
 * <pre>
 * if (Example_Class::example()) {
 *    echo "I am an example.";
 * }
 * </pre>
 * 
 * @package		The package
 * @author		Michaël V. Dandrieux <mvd@cafecentral.fr>
 * @author		Sylvain Frigui <sf@cafecentral.fr>
 * @copyright	Copyright © 2004-2013, Café Central
 * @license		http://www.cafecentral.fr/fr/licences GNU Public License
 * @access		public
 * @link		http://www.cafecentral.fr/fr/wiki
 */
/********************************************************************************************/
//	Some binds
/********************************************************************************************/
	$_APP->bind_file('css', 'master/css/header.css');
	$_APP->bind_file('script', 'master/js/header.js');
	$_APP->bind_file('css', 'master/css/tabs.css');
	$_APP->bind_file('script', 'master/js/tabs.js');
	$_APP->bind_file('css', 'master/css/options.css');
	$_APP->bind_file('script', 'master/js/options.js');
	
/********************************************************************************************/
//	Some vars
/********************************************************************************************/
	$page = i('page', current);
	$sections = $page['section']->unfold();
	$handled_item = (isset($_GET['item'])) ? $_GET['item'] : null;

//	For the title
	$current = null;
	$back = null;
	$link = null;
	
	if (i('page', current)['key'] == 'home')
	{
		$invite = '<div class="clapalong">Clap along if you feel like <span>adding</span> a <span>page</span> called <span>something new</span>.</div>';
	}
	else $invite = null;

/********************************************************************************************/
//	The title
/********************************************************************************************/
	switch (i('page', current)['key'])
	{	
	//	Edit
		case 'edit':
			$structure = i('item', $_GET['item'], $_SESSION['pref']['handled_env']);
		//	We have an item already
			if (isset($_GET['id']))
			{
				$item = i($_GET['item'], $_GET['id'], $_SESSION['pref']['handled_env']);
				$link = $item->listing();
				$current = (isset($item['title']) && !$item['title']->is_empty()) ? $item['title'] : $item['key'];
			}
		//	New item
			else
			{
				$link = i($_GET['item'], null, $_SESSION['pref']['handled_env'])->listing();
				$current = new attrString('[I\'m so fresh, i don\'t even have a title]');
			}
		//	Go
			$back = $structure['title'];
			break;
			
	//	List
		case 'list':
			$structure = i('item', $_GET['item'], $_SESSION['pref']['handled_env']);
			$item = i('page', 'home');
			$link = $item['url'];
			$back = $item['title'];
			$current = $structure['title'];
			break;
			
	//	App
		case 'app':
			$app = new app($_GET['app']);
			$ini = $app->get_ini();
			$page = i('page', 'app');
			$link = $page['url'];
			$back = $page['title'];
			$current = new attrString($ini['about']['title']);
			break;
			
	//	Home
		case 'home':
			$item = i('page', 'home');
			$link = 'javascript:openSite();';
			$back = i('site', current)['title'];
			$current = i('page', current)['title'];
			break;
		
		default:
			$item = i('page', 'home');
			$link = $item['url'];
			$back = $item['title'];
			$current = i('page', current)['title'];
			break;
	}
	
/********************************************************************************************/
//	Remove sections on the fly
/********************************************************************************************/
//	List
	$onlyfor['list'] = array(
		'tree' => array('page'),
	);
	$stripfrom['list'] = array(
		'live' => array('page'),
	);
	
//	Edit
	$onlyfor['edit'] = array(
		'zoning' => array('site', 'version', 'page'),
		'feed' => array('page'),
		'appconfig' => array('app'),
		'appini' => array('app'),
	);
	
//	Add and remove sections
	foreach ($sections as $section)
	{
		if (
		//	Some sections are only for some pages
			(isset($onlyfor[$page['key']->get()][$section['key']->get()]) && !in_array($handled_item, $onlyfor[$page['key']->get()][$section['key']->get()])) OR
		//	Some sections must be striped from some pages
			(isset($stripfrom[$page['key']->get()][$section['key']->get()]) && in_array($handled_item, $stripfrom[$page['key']->get()][$section['key']->get()]))
		) {
		//	Delete relation
			$i = array_search($section->get_nickname(), $page['section']->get());
			unset($sections[$i]);
		}
	}

/********************************************************************************************/
//	Fetch the altered sections
/********************************************************************************************/
//	Find the default section
	if (!$page['sectiondefault']->is_empty())
	{
		foreach ($page['sectiondefault']->unfold() as $key => $default)
		{
			foreach ($sections as $key => $section)
			{
				if ($section['key'] == $default['key'])
				{
					$defaultSection = $default['key'];
					break;
				}
			}
		}
	}
?>