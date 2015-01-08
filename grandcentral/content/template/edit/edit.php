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
//	Some vars
/********************************************************************************************/
	require 'adminItemForm.class.php';
	
//	Env
	$handled_env = $_SESSION['pref']['handled_env'];
//	Item
	$handled_item = (isset($_GET['item'])) ? $_GET['item'] : null;
	$handled_id = (isset($_GET['id'])) ? $_GET['id'] : null;
//	Fetch item
	$item = i($handled_item, null, $handled_env);
	if ($handled_item && $handled_id)
	{
		$item->get(array
		(
			'id' => $handled_id,
			'status' => null,
		));
	}
//	You can prefill the form through _GET (&fill[title]=something&fill[system]=0...)
	if (isset($_GET['fill']))
	{
		foreach ($_GET['fill'] as $key => $value)
		{
			if (isset($item[$key])) $item[$key] = $value;
		}
	}
	
/********************************************************************************************/
//	One exception for the workflow
/********************************************************************************************/
	if ($handled_item == 'workflow' && $item->exists())
	{
	//	Create a new temporary item
		$tmp = i($item['item']->get(), null, $handled_env);
	//	Fetch the data from the workflow
		$item = $item['data']->get();
	}

/********************************************************************************************/
//	Build front
/********************************************************************************************/
	if (!empty($handled_item))
	{
		$form = new adminItemForm($item);
	}
	else
	{
		$form = null;
	}

//	title
	$str = isset($item['title']) ? $item['title']->cut(45) : '';
	$title = ($handled_id) ? '<a href="'.$item->listing().'">'.i('item', $item->get_table(), $handled_env)['title'].'</a> '.$str : 'New <a href="'.$item->listing().'">'.i('item', $item->get_table(), $handled_env)['title'].'</a>';
	# fallback
	if (!$title) $title = $handled_item.' #'.$handled_id;
	
?>