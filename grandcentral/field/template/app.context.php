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
//	Value template
	$valueTemplate = (isset($_POST['valueTemplate'])) ? $_POST['valueTemplate'] : null;

//	Value param
	parse_str(urldecode($_POST['name']), $name);
	$form = key($name);
	$field = key($name[$form]);
	$valueParam = array();
	if ($_POST['valueParam'])
	{
		foreach ($_POST['valueParam'] as $param)
		{
			$pattern = '/\[([a-zA-Z0-9_\-]*)\]$/';
			preg_match($pattern, $param['name'], $matches);
			$valueParam[$matches[1]] = $param['value'];
		}
	}
	$app = app($_POST['valueApp'], null, $valueParam);

//	Content-type
	$valueContenttype = (isset($_POST['valueContenttype'])) ? $_POST['valueContenttype'] : null;
	
/********************************************************************************************/
//	Template list
/********************************************************************************************/
//	Check if the app has some templates
	$templates = $app->get_templates($valueContenttype, $_POST['env']);
	// print'<pre>';print_r($templates);print'</pre>';
	if ($templates)
	{
	//	We need templates, not indexes
		foreach ($templates as $template) $tmp[$template] = $template;
		$templates = $tmp;
		
		$fparams = array(
			'values' => $templates,
			'valuestype' => 'array',
			'value' => $valueTemplate,
		);
		$fieldTemplate = new fieldSelect($_POST['name'].'[template]', $fparams);
	}	
/********************************************************************************************/
//	FROM APP.PARAM Champ paramètres des apps
/********************************************************************************************/	
//	récupération des paramètres de l'app
	$params = $app->get_default_param();
	// print'<pre>';print_r($params);print'</pre>';exit;
	// print '<pre>';print_r($APP);print'</pre>';
	$fields = array();
//	construction de la liste des champs
	foreach ((array) $params as $key => $value)
	{
	//	recherche de la valeur sélectionnée
		preg_match('/\[([a-z_0-9]*)\]/i', $value, $matches);
	//	suppression des caractères inutiles
		$value = str_replace(array(' ','[', ']'), '', $value);
	//	création des listes
		$values = explode(',', $value);

	//	champ text et number
		if (count($values) == 1)
		{
			$p = array(
				'label' => $key,
				'value' => $value
			);
		//	number
			if (is_numeric($value)) $fields[$key] = new fieldNumber($key, $p);
		//	text
			else $fields[$key] = new fieldText($key, $p);
		}
	//	champ bool et select
		else
		{
		//	bool
			if (count($values) == 2 && in_array('true', $values) && in_array('false', $values))
			{
				$p = array(
					'label' => $key,
					'labelbefore' => true
				);
				if (isset($matches[1]) && $matches[1] == 'true') $p['value'] = $matches[1];
				$fields[$key] = new fieldBool($key, $p);
			}
		//	select
			else
			{
				$p = array(
					'label' => $key,
					'values' => $values,
					'valuestype' => 'array'
				);
				if (isset($matches[1])) $p['value'] = $matches[1];
				$fields[$key] = new fieldSelect($key, $p);
			}
		}
	}
?>