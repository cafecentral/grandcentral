<?php
/**
 * Classe du champ multipleselect
 * 
 * Permet d'afficher un sélecteur permettant, par drag and drop, de sélectionner une liste de valeurs et de les ordonner
 * 
 * @package		form
 * @author		Michaël V. Dandrieux <mvd@cafecentral.fr>
 * @author		Sylvain Frigui <sf@cafecentral.fr>
 * @access		public
 * @link		http://www.cafecentral.fr/fr/wiki
 */
class fieldMultipleselect extends _fieldsSelector
{
	protected $datatype = array('rel, string');
/**
 * Affecte un placeholder au champ
 * 
 * @param	string	le placeholder
 * @access	public
 */
	public function set_placeholder($text)
	{
		if (!empty($text)) $this->attrs['placeholder'] = $text;
		return $this;
	}
/**
 * Retourne le placeholder au champ
 * 
 * @return	string	le placeholder
 * @access	public
 */
	public function get_placeholder()
	{
		return (isset($this->attrs['placeholder'])) ? $this->attrs['placeholder'] : '';
	}
}
?>