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
 * @copyright	Copyright © 2004-2012, Café Central
 * @license		http://www.cafecentral.fr/fr/licences GNU Public License
 * @access		public
 * @link		http://www.cafecentral.fr/fr/wiki
 */
/********************************************************************************************/
//	Bind
/********************************************************************************************/
	$_APP->bind_script('js/notes.js');
	$_APP->bind_css('css/notes.css');

/********************************************************************************************/
//	Some vars
/********************************************************************************************/
//	Default Max notes displayed
	$displayNotes = 10;
//	Comes via Ajax as well
	if (isset($_POST['displayNotes'])) $displayNotes = $_POST['displayNotes'];
	
/********************************************************************************************/
//	note source
/********************************************************************************************/
//	Via ajax
	if (isset($_POST['item']))
	{
		list($item, $id) = explode('_', $_POST['item']);
	}
//	Inline in the page
	else if (isset($_GET['id']) && isset($_GET['item']))
	{
		$item = $_GET['item'];
		$id = $_GET['id'];
	}

/********************************************************************************************/
//	Get notes
/********************************************************************************************/
	if (isset($item) && isset($id))
	{
		$p = array(
			'item' => $item,
			'itemid' => $id,
			'status' => 'live',
			'order()' => 'created DESC',
			'limit()' => $displayNotes,
		);
		$notes = cc('note', $p, $_SESSION['pref']['handled_env']);
		
	//	Reverse order
		$notes->data = array_reverse($notes->data);

/********************************************************************************************/
//	Get the form
/********************************************************************************************/
		$form = new item_form('inline_note');

	//	Recreate form
	/*	$form['field'] = array(
			'descr' => array(
				'key' => 'descr',
				'type' => 'textarea',
				'placeholder' => 'Dont forget the place holder',
			),
			'table' => array(
				'key' => 'table',
				'type' => 'hidden',
			),
			'item' => array(
				'key' => 'item',
				'type' => 'hidden',
			),
			'itemid' => array(
				'key' => 'itemid',
				'type' => 'hidden',
			),
			'status'=>array(
				'key' => 'status',
				'type' => 'hidden',
			),
		);
		$form->save();
	*/
	
	//	Pre-set values
	//	$form->set('table', 'value', 'note');
	//	$form->set('item', 'value', $item);
	//	$form->set('itemid', 'value', $id);
	//	$form->set('status', 'value', 'live');

/********************************************************************************************/
//	Event Source
/********************************************************************************************/
		$arg = array(
			'app' => 'section',
			'theme' => 'notes',
			'template' => 'notes',
		);
		$EventSource = 	cc('page', 'api-eventstream')->link($arg);
	}
?>