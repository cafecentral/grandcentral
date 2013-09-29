<?php
/**
 * Classe du champ radio
 * 
 * Affiche une liste de radios
 * 
 * @package		form
 * @author		Michaël V. Dandrieux <mvd@cafecentral.fr>
 * @author		Sylvain Frigui <sf@cafecentral.fr>
 * @access		public
 * @link		http://www.cafecentral.fr/fr/wiki
 */
class fieldRadio extends _fieldsSelector
{
	protected $datatype = array('rel, string');
/**
 * Obtenir la liste de tous les attributs du champ
 * 
 * @return	array	le tableau des attributs du champ
 * @access	public
 */
	public function get_attrs()
	{
		return $this->attrs;
	}
/**
 * Obtenir la définition des propriétés du champ
 * 
 * @return	array 	la liste des propriétés et leurs définitions
 * @access	public
 * @static
 */
	public static function get_defined_properties()
	{
		$properties = parent::get_defined_properties();
		unset($properties['min']);
		unset($properties['max']);	
		return $properties;
	}
}
?>