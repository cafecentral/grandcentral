<?php
/**
 * The generic item of Café Central
 *
 * @package  Core
 * @author   Sylvain Frigui <sf@cafecentral.fr>
 * @access   public
 * @see      http://www.cafecentral.fr/fr/wiki
 */
class itemPage extends _items
{
	const child = 'child';
	protected $child = false;
/**
 * Détermine si la page dispose d'une section reader
 *
 * @access	public
 */
	public function has_reader()
	{
		return (is_array(registry::get(registry::reader_index, $this->get_nickname()))) ? true : false;
	}
/**
 * Rechercher une page avec une url
 *
 * @access	public
 */
	public function get_by_url($url = null)
	{
		if (empty($url)) $url = '/';
		
		$cache = app('cache');
		$fileCache = $cache->get_templateroot().'/page/'.md5($this['url'].$url);
		
		// dans le cache
		//if (is_file($fileCache))
		//{
		//	$this->data = unserialize(file_get_contents($fileCache));
		//}
		// création du cache
		//else
		//{
			$this->get(array('url' => $url));
		//	file_put_contents($fileCache, serialize($this->data));
		//}
	}
/**
 * Devine la page à afficher
 *
 * @access	public
 */
	public function guess()
	{
		// recherche de la page
		$this->get_by_url(URLR);
		//	si la page n'existe pas, on éclate l'url et on fait une recherche aproximative
		if (!$this->exists())
		{
			$hash = mb_substr(URLR, 0, mb_strrpos(URLR, '/'));
			// chargement de la page de home
			$this->get_by_url($hash);

			if (!$this->exists() OR ($this->exists() && !$this->has_reader()))
			{
				$this->get_by_url('/404');
			}
		}
		// application des droits
		if ($this->exists() && !$_SESSION['user']->can('see', $this))
		{
			$this->get('login');
		}
		// si on ne trouve rien, on renvoi une erreur
		if (!$this->exists())
		{
			$this->get_by_url('/404');
		}
	}
/**
 * Save item into database
 *
 * @access  public
 */
	public function save()
	{
	//	Sauvegarde
		parent::save();
	//	gestion du parent de la page
		if ($this['system']->get() === false && $this['key']->get() != 'home')
		{
			$parent = false;
		//	si la page existe
			if (!$this['id']->is_empty())
			{
			//	on vérifie si la page à un parent
				$q = 'SELECT COUNT(item) as count FROM _rel WHERE item ="page" AND `key` = "child" AND rel="page" AND relid='.$this['id']->get();
				$db = database::connect($this->get_env());
				$r = $db->query($q);
				if ($r['data'][0]['count'] > 0)
				{
					$parent = true;
				}
			}
			
			if ($parent === false)
			{
				$home = i('page', 'home', $this->get_env());
				$home['child']->add($this);
				$home->save();
			}
		}
	}
/**
 * Envoie les entêtes HTTP de la page
 *
 * @access	public
 */
	public function header()
	{
	//	Content types
		$mime = $this->get_authorised_mime();
	//	Convert the GC content type to MIME content types
		$content_type = $mime[$this['type']['content_type']];
		
	//	Print the header
		header('HTTP/1.0 '.$this['type']['http_status']);
		header('Content-Type: '.$content_type.'; charset=utf-8');
	}
/**
 * Envoie les entêtes HTTP de la page
 *
 * @access	public
 */
	public function get_authorised_mime()
	{
	//	Content types
		$mime = array(
			'xml' => 'application/xml',
			'json' => 'application/json',
			// 'json' => 'text/html',
			'html' => 'text/html',
			'routine' => 'text/x-php',
			'eventstream' => 'text/event-stream',
		);
		return $mime;
	}
/**
 * Affiche les section de la page dans leur zone respectives
 *
 * @access	public
 */
	public function bind_section()
	{
		foreach ($this['section']->unfold() as $section)
		{
			$section->__tostring();
		}
	}
/**
 * Display the page
 *
 * @return	string	le code html de la page
 * @access	public
 */
	public function __tostring()
	{
	//	prepare page display
		$prepareFunction = '_prepare_'.$this['type']['key'];
	//	error
		if (!method_exists($this, $prepareFunction))
		{
			trigger_error('Cannot display page. I need a valid type.', E_USER_ERROR);
		}
	//	headers
		$this->header();

	//	display
		return $this->$prepareFunction();
	}
/**
 * Prepare display of a content page
 *
 * @access	public
 */
	private function _prepare_content()
	{
	//	call master
		$master = new master($this);
		// print'<pre>';print_r($master);print'</pre>';
	//	display master
		return $master->__tostring();
	}
/**
 * Prepare display of a header page
 *
 * @access	public
 */
	private function _prepare_header()
	{
	//	recherche du premier enfant
		$child = $this[self::child]->get();
	//	redirection
		if (count($child) > 0)
		{
			$child = i($child[0]);
			header('Location:'.$child['url'], false);
		}
	//	erreur
		trigger_error('This header page needs a valid <strong>'.self::child.'</strong> relation to work properly.', E_USER_ERROR);
	}
/**
 * Prepare display of a link page
 *
 * @access	public
 */
	private function _prepare_link()
	{
	//	redirection
		if (filter_var($this['type']['url'], FILTER_VALIDATE_URL))
		{
			header('Location:'.$this['type']['url'], false);
		}
	//	erreur
		trigger_error('This link page needs a valid <strong>url</strong> to work properly.', E_USER_ERROR);
	}
/**
 * Get a bunch with all the children of the page
 *
 * @access	public
 */
	public function get_children()
	{
		return $this['child']->unfold();
	}
/**
 * Get a bunch with the direct parent of the page or all of his parents
 *
 * @param	int		deep of the ancestors
 * @return	bunch	a bunch of pages
 * @access	public
 */
	public function get_parent()
	{
		$page = $this->get_nickname();
		$parent = $this->_get_parent_nickname($page);
	//	get the items
		$b = new bunch(null, null, $this->get_env());
	//	return
		return $b->get_by_nickname($parent);
	}
/**
 * Get a bunch with the direct all of the ancestors of the page
 *
 * @return	bunch	a bunch of pages
 * @access	public
 */
	public function get_parents()
	{
		$page = $this->get_nickname();
	//	get ancestors
		while ($page = $this->_get_parent_nickname($page))
		{
			$parent[] = $page;
			$i++;
		}
		$parent = (isset($parent)) ? array_reverse($parent) : null;
	//	get the items
		$b = new bunch(null, null, $this->get_env());
	//	return
		return $b->get_by_nickname($parent);
	}
/**
 * Get a bunch with the pages at the same level of the current page
 *
 * @param	bool	if true return also the current page (default : true)
 * @return	bunch	a bunch of pages
 * @access	public
 */
	public function get_siblings($params = null, $self = true)
	{
		$tree = (array) registry::get(registry::legacy_index);
		
		foreach ($tree as $parent => $children)
		{
			if (in_array($this->get_nickname(), $children))
			{
				$b = new bunch(null, null, $this->get_env());
				if ($self === false)
				{
					$index = array_search($this->get_nickname(), $children);
					unset($children[$index]);
				}
				return $b->get_by_nickname($children);
			}
		}
	//	return
		return null;
	}
/**
 * Check if the page given in parameter is the parent of $this
 *
 * @param	mixed	an itemPage or a page nickname
 * @return	bool	true if parent exists, false otherwise
 * @access	public
 */
	public function is_child_of($parent)
	{
		$page = $this->get_nickname();
		$parent = (is_a($parent, 'itemPage')) ? $parent->get_nickname() : $parent;
		
		while ($page = $this->_get_parent_nickname($page))
		{
			if ($parent == $page) return true;
		}
		return false;
	}
/**
 * Check if the page given in parameter is the child of $this
 *
 * @param	mixed	an itemPage or a page nickname
 * @return	bool	true if parent exists, false otherwise
 * @access	public
 */
	public function is_parent_of($child)
	{
		$page = $this->get_nickname();
		$child = (is_a($child, 'itemPage')) ? $child->get_nickname() : $child;
		
		while ($child = $this->_get_parent_nickname($child))
		{
			if ($child == $page) return true;
		}
		
		return false;
	}
/**
 * Get a bunch with the direct parent of the page or all of his parents
 *
 * @param	mixed	an itemPage or a page nickname
 * @access	public
 */
	protected function _get_parent_nickname($page)
	{
		$return = false;
		
		// var_dump();
		if (is_a($page, 'itemPage') || mb_strpos($page, 'page_') !== false)
		{
			// var_dump($bool);
			$nickname = (is_a($page, 'itemPage')) ? $page->get_nickname() : $page;
			$tree = (array) registry::get(registry::legacy_index);
			// print'<pre>';print_r($tree);print'</pre>';
			foreach ($tree as $parent => $children)
			{
				if (in_array($nickname, $children))
				{
					$return = $parent;
					break;
				}
			}
		}
		
		return $return;
	}
/**
 * Get the level of the page in the site tree
 *
 * @return	int		the level of the current page
 * @access	public
 */
	public function get_level()
	{
		$parent = array();
		$page = $this->get_nickname();
		while ($page = $this->_get_parent_nickname($page))
		{
			$parent[] = $page;
		}
		return count($parent);
	}
/**
 * On charge toutes les urls des pages du site
 *
 * @param	mixed	la valeur à lire dans le registre. Vous pouvez mettre autant d'arguments que vous le souhaitez.
 * @access	public
 */
/**
 * On charge toutes les url et les types de pages
 *
 * @access	protected
 */
	public static function register()
	{
		$db = database::connect('site');
		// on recherche toutes les urls des pages
		$q = 'SELECT `id`, `url` FROM `page`';
		$r = $db->query($q);
		foreach ($r['data'] as $page)
		{
			$urls['page_'.$page['id']] = $page['url'];
		}
		// on recherche les readers dans le table section
		$q = 'SELECT `id`, `app` FROM `section` WHERE `app` LIKE "%\"app\":\"reader\"%"';
		$r = $db->query($q);
		// traitement de la requête pour stockage
		$hash = null;
		$readersid = array();
		if ($r['count'] > 0)
		{
			foreach ($r['data'] as $reader)
			{
				$readersId[] = $reader['id'];
				$readersTable[$reader['id']] = json_decode($reader['app'], true);
			}
			$hash = ' OR (`rel`="section" AND `relid` IN ('.implode(',',$readersId).'))';
		}
		// on recherche les pages liées aux readers et les liaisans entre les pages
		$q = 'SELECT * FROM `_rel` WHERE `item`="page" AND (`key`="child"'.$hash.') ORDER BY `itemid`, `position`';
		$r = $db->query($q);
		$readers = $tree = array();
		foreach ($r['data'] as $rel)
		{
			if ('child' == $rel['key'])
			{
				$tree[$rel['item'].'_'.$rel['itemid']][] = $rel['rel'].'_'.$rel['relid'];
			}
			else
			{
				$readers[$rel['item'].'_'.$rel['itemid']][] = $readersTable[$rel['relid']]['param']['item'];
			}
		}
		// mise en registre
		registry::set(registry::url_index, $urls);
		registry::set(registry::legacy_index, $tree);
		registry::set(registry::reader_index, $readers);
	}
	
/**
 * Delete cache file of the structures loaded into the registry
 *
 * @access  public
 */
	private function register_reset()
	{
		$cache = app('cache');
		$fileCache = $cache->get_templateroot().'registry/'.md5('url');
		
		if (is_file($fileCache))
		{
			unlink($fileCache);
		}
	}
}
?>