<?php
/**
 * Selectors Library
 *
 * @package  Core
 * @author   Sylvain Frigui <sf@cafecentral.fr>
 * @access   public
 * @see      http://www.cafecentral.fr/fr/wiki
 */

/**
 * Main selector
 *
 * @param	string	table
 * @param	mixed	parameter or array of parameters
 * @param	string	admin ou site
 * @return	mixed	an item or a bunch
 * @access	public
 */
	function cc($table, $params = null, $env = env)
	{
		switch (true)
		{
		//	bunch de tous les items de la table
			case $params == all:
				// print '<pre>';print_r('all');print'</pre>';
				return new bunch($table, null, $env);
				break;
		//	item de l'environnement
			case $params == current:
				// print '<pre>';print_r('current');print'</pre>';
				return registry::get(current, $table);
				break;
		//	bunch d'items en fonction des paramètres
			case is_array($params):
				// print '<pre>';print_r('bunch');print'</pre>';
				return new bunch($table, $params, $env);
				break;
		//	nickname
			case is_null($params) && mb_strpos($table, '_'):
				list($table, $id) = explode('_', $table);
				// print'<pre>';print_r($table);print'</pre>';
				return item::create($table, $id, $env);
				break;
		//	nouvel item
			case is_null($params):
				// print '<pre>';print_r('new');print'</pre>';
				return item::create($table, null, $env);
				break;
		//	item
			case is_string($params):
			case is_int($params):
				// print '<pre>';print_r('item');print'</pre>';
				return item::create($table, $params, $env);
				break;
		}
	}
/**
 * Euro selector
 *
 * @param	string	table
 * @param	mixed	parameter or array of parameters
 * @param	string	admin ou site
 * @return	mixed	an item or a bunch
 * @access	public
 */
	function €($table, $params = null, $env = env)
	{
		return cc($table, $params, $env);
	}
	
/**
 * Attr factory
 *
 * @param	string	table
 * @param	mixed	parameter or array of parameters
 * @param	string	admin ou site
 * @return	mixed	an item or a bunch
 * @access	public
 */
	function attr($type, $value = null, $params = null)
	{
		$class = 'attr'.ucfirst($type);
		$attr = new $class($value, $params);
		return $attr;
	}
?>