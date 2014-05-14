<?php
/**
 * Integer formated attributes handling class
 *
 * @package 	Core
 * @author		Sylvain Frigui <sf@cafecentral.fr>
 * @access		public
 * @link		http://www.cafecentral.fr/fr/wiki
 */
class attrItem extends _attrs
{
/**
 * Set attribute
 *
 * @param	string	la variable
 * @return	string	une string
 * @access	public
 */
	public function set($data)
	{
		$this->data = (string) $data;
		return $this;
	}
/**
 * Definition mysqld
 *
 * @return	string	la définition mysql
 * @access	public
 */
	public function mysql_definition()
	{
	//	definition
		$definition = '`'.$this->params['key'].'` varchar(64) CHARACTER SET '.database::charset.' COLLATE '.database::collation.' NOT NULL';
	//	retour
		return $definition;
	}
/**
 * Definition index mysql
 *
 * @return	string	la définition mysql
 * @access	public
 */
	public function mysql_index_definition()
	{
	//	definition
		$definition = 'KEY `'.$this->params['key'].'` (`'.$this->params['key'].'`)';
	//	retour
		return $definition;
	}
/**
 * Default field attributes for Version	
 *
 * @param	string	la variable
 * @return	string	une string
 * @access	public
 */
	public static function get_properties()
	{
	//	Start with the default for all properties
		$params = parent::get_properties();
	//	Somes specifics for this attr
		$params['rel'] = array(
            'name' => 'param',
            'type' => 'bunch',
            'label' => 'Build your list of items',
        );
	//	Return
		return $params;
	}
}
?>