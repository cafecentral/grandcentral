<?php
/**
 * String formated attributes handling class
 *
 * @package 	Core
 * @author		Sylvain Frigui <sf@cafecentral.fr>
 * @access		public
 * @link		http://www.cafecentral.fr/fr/wiki
 */
class attrRel extends _attrs
{
	const table = '_rel';
	protected $env;
/**
 * Ajouter une ou plusieurs relations
 *
 * @param	mixed	arrays or bunch of _items or nicknames
 * @access	public
 */
	public function set($rel)
	{
		if (empty($rel)) return $this;
	//	mise en conformité de l'objet
		if (!is_array($rel) && !is_a($rel, 'bunch')) $rel = array($rel);
	//	on vide
		$this->data = null;
	//	affectation
		foreach ($rel as $value)
		{
			$this->add($value);
		}
		return $this;
	}
/**
 * Add a rel
 *
 * @param	string	la variable
 * @return	string	une string
 * @access	public
 */
	public function add($rel)
	{
	//	on transforme l'objet en nickname
		if (is_a($rel, '_items'))
		{
			$rel = $rel->get_nickname();
		}
	//	affectation
		$this->data[] = $rel;
		return $this;
	}
/**
 * Set attribute
 *
 * @param	string	la variable
 * @return	string	une string
 * @access	public
 */
	public function set_min($value)
	{
		$this->params['min'] = $value;
		return $this;
	}
/**
 * Set attribute
 *
 * @param	string	la variable
 * @return	string	une string
 * @access	public
 */
	public function set_max($value)
	{
		$this->params['max'] = $value;
		return $this;
	}
/**
 * Set attribute
 *
 * @param	string	la variable
 * @return	string	une string
 * @access	public
 */
	public function set_param($value)
	{
		$this->params['param'] = $value;
		return $this;
	}
/**
 * Set attribute
 *
 * @param	string	la variable
 * @return	string	une string
 * @access	public
 */
	public function set_env($value)
	{
		$this->env = $value;
		return $this;
	}
/**
 * xxxx
 *
 * @param	string	la variable
 * @return	string	une string
 * @access	public
 */
	public function __tostring()
	{
		return '<pre>'.print_r($this->data, true).'</pre>';
	}
/**
 * xxxx
 *
 * @param	string	la variable
 * @return	string	une string
 * @access	public
 */
	public function unfold()
	{
		$bunch = new bunch(null, null, $this->env);
		$bunch->get_by_nickname($this->data);
		
		return $bunch;
	}
}
?>