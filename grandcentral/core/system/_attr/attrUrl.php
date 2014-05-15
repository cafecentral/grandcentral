<?php
/**
 * String formated attributes handling class
 *
 * @package 	Core
 * @author		Sylvain Frigui <sf@cafecentral.fr>
 * @access		public
 * @link		http://www.cafecentral.fr/fr/wiki
 */
class attrUrl extends _attrs
{
	protected $item;
/**
 * Set string attribute
 *
 * @param	string	la variable
 * @return	string	une string
 * @access	public
 */
	public function database_get()
	{
		// print'<pre>';print_r($this->params);print'</pre>';
		if (empty($this->data))
		{
			$this->data = $this->_slugify($this->params['name']->get());
		}
		$this->data = preg_replace('#(\[[^\]]*\])#', '', $this->data);
	//	nettoyage des [] existants
		// if ($this->params['status'] != 'live')
		// 		{
		// 			$slug = new slug();
		// 			$this->data .= '['.$slug->makeSlugs($this->params['status']).']';
		// 		}
		return $this->data;
	}
/**
 * Set string attribute
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
 * xxxx
 *
 * @param	string	la variable
 * @return	string	une string
 * @access	public
 */
	public function set_reader($value)
	{
		$this->params['reader'] = $value;
		$this->params['table'] = registry::get(registry::reader_index, $value, 'url');
	}
/**
 * Get complete item url
 *
 * @return	string	url
 * @access	public
 */
	public function attach(_items &$item)
	{
		$this->params['table'] = $item->get_table();
		$this->params['env'] = $item->get_env();
		$this->params['version'] = (isset($item['version']) && !$item['version']->is_empty()) ? $item['version'] : null;
		$this->params['status'] = $item['status'];
		$this->params['name'] = $item['title']->get();
		// print'<pre>';print_r(registry::get_constants());print'</pre>';
	}
/**
 * php http_build_query() on url
 *
 * @param	array	get arguments
 * @return	string	url
 * @access	public
 */
	public function args($arg)
	{
		// print'<pre>';print_r($arg);print'</pre>';
		$url = $this->__tostring();
		$url .= (!empty($arg)) ? '?'.http_build_query($arg) : '';
		return $url;
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
		$url = '';
		// version url
		if (is_null($this->params['version']))
		{
			$url = cc($this->params['env'], current)['version']->get_url();
		}
		else
		{
			$version = constant(mb_strtoupper($this->params['env']).'_VERSION');
			$url = constant('VERSION_'.mb_strtoupper($version)).$url;
		}
		// reader
		if ($this->params['table'] != 'page')
		{
			foreach (registry::get(registry::reader_index) as $page => $tables)
			{
				if (in_array($this->params['table'], $tables))
				{
					$url = registry::get(registry::url_index, $page);
					break;
				}
			}
		}
		// retunr
		return $url.$this->get();
	}
/**
 * Definition mysql
 *
 * @return	string	la définition mysql
 * @access	public
 */
	public function mysql_definition()
	{
	//	definition
		$definition = '`'.$this->params['key'].'` varchar(255) CHARACTER SET '.database::charset.' COLLATE '.database::collation.' NOT NULL';
	//	retour
		return $definition;
	}
/**
 * 
 *
 * @param	string	la variable
 * @return	string	une string
 * @access	public
 */
	protected function _slugify($string)
	{
		$string = trim(trim($string), '-');
		
		if (mb_substr($string, 0, 1) == '/')
		{
			$string = mb_substr($string, 1);
		}
		
		$slug = new slug();
		return '/'.$slug->makeSlugs($string);
	}
/**
 * Get the abbreviation of the link
 *
 * @access	public
 */
	public function abbr()
	{
	//	return '['.$this->item->get_nickname().']';
	}
/**
 * Default field attributes for updated	
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
		# $params['somefield'] = array();
		$params['key']['value'] = 'url';
		$params['key']['readonly'] = true;
		unset($params['required']);
	//	Return
		return $params;
	}
}
?>