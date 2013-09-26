<?php
/**
 * La classe de ressources de la base de données
 *
 * Créer une connexion à la bdd et exécuter des requêtes préparées
 *
 * ex :
 * $db = database::connect('site');
 * $param = array('key' => 'home');
 * $home = $db->query('SELECT * FROM page WHERE `key` = :key', $param);
 *
 * @package  Core
 * @author   Sylvain Frigui <sf@cafecentral.fr>
 * @access   public
 * @see      http://www.cafecentral.fr/fr/wiki
 */
class database
{
	const charset = 'utf8';
	const collation = 'utf8_unicode_ci';
	
	private static $instance = array();
	private static $env;
	private static $query_count = 0;
	private static $transactions;
	
	private $_pdo;
	private $_spooler = array();

/**
 * Configure l'objet PDO et crée la connexion à la base de données
 *
 * @param	string  le type de la bdd
 * @param	string  le host de la bdd
 * @param	string  le nom de la bdd
 * @param	string  le user de la bdd
 * @param	string  le password de la bdd
 * @access	private
 */
	private function __construct($db_type, $db_host, $db_name, $db_user, $db_password)
	{
		try
		{
		//	récupération du data source name
			$db_dsn = 'mysql:host='.$db_host.';dbname='.$db_name;
		//	connexion
    		$this->_pdo = new PDO($db_dsn, $db_user, $db_password);
		//	pour que les erreurs généréer soient des exceptions
			$this->_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		//	configuration de la connexion
			$this->_pdo->query('SET NAMES '.self::charset);
			$this->_pdo->query('SET @@collation_connection = '.self::collation);
		}
	//	si une erreur se produit, on envoie la sentinel
		catch (PDOException $e) {
			$l = array(
				'What went wrong' => 'Can\'t connect to database '.$db_name.': '.$e->getMessage(),
				'function' => __METHOD__
			);
			sentinel::log(E_ERROR, $l);
		}
	}

/**
 * Obtenir une connexion à la base de données
 * 
 * ex : $db = database::connect('site');
 * $db->query('SELECT * FROM page');
 *
 * @param	string	"site" ou "admin". Par défaut, l'environnement en cours
 * @return	database	l'instance site ou admin de database
 * @access	public
 * @static
 */
	public static function connect($env = env)
	{
		self::$env = $env;
	//	à la manière du singleton
		if (empty(self::$instance[self::$env]))
		{
			$prefix = strtoupper(self::$env);
			$db = array(
				'type' => constant($prefix.'_DB_TYPE'),
				'host' => constant($prefix.'_DB_HOST'),
				'name' => constant($prefix.'_DB_NAME'),
				'user' => constant($prefix.'_DB_USER'),
				'password' => constant($prefix.'_DB_PASSWORD'),
			);
			self::$instance[self::$env] = new database($db['type'], $db['host'], $db['name'], $db['user'], $db['password']);
		}
		return self::$instance[self::$env];
	}

/**
 * Définir le Data Source Name pour la connexion à la base de données
 *
 * @param	string	le type de la bdd
 * @param	string	le host de la bdd
 * @param	string	le nom de la bdd
 * @return	string	le dsn
 * @access	private
 */
	private function _get_dsn($db_type, $db_host, $db_name)
	{
		// print '<pre>';print_r(get::$site);print'</pre>';
		if (isset($this->db_types[$db_type]))
		{
			$from = array('[db_host]', '[db_name]');
			$to = array($db_host, $db_name);
			return str_replace($from, $to, $this->db_types[$db_type]);
		}
		else
		{
			$l = array(
	    		'What went wrong ?' => 'Sorry, <strong>'.$db_type.'</strong> is not supported.',
		    );
		   	sentinel::log(E_ERROR, $l);
		}		
	}
/**
 * Faire des requêtes sur la base de données en mode transactionnel
 *
 * @param	string  la requête SQL à exécuter sur la base
 * @param	array 	le tableau de paramètres pour les requêtes préparées
 * @return	array	le résultat de la requête
 * @access	public
 */
	public function query($query, $p = null)
	{
		try
		{
		//	benchmark
			$time_start = microtime(true);
		//	var
			$return = array(
				'base' => self::$env,
				'query' => $query,
				'param' => $p,
				'time' => null,
				'count' => 0,
				'data' => array()
			);
			// if(SITE_DEBUG) $return['debug'] = debug_backtrace();
		//	begin transaction
			$this->_pdo->beginTransaction();
		//	Préparation
			$stmt = $this->_pdo->prepare($query);
		//	Exécution de la requête préparée
			if ($stmt->execute($p))
			{
				$return['count'] = $stmt->rowCount();
				if ($return['count'] > 0 && strstr($query, 'SELECT '))
				{
					while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
					{
						$return['data'][] = $row;
					}
				}
			}
		//	commit
			$this->_pdo->commit();
		//	free memory
			unset($stmt);
		//	complements
			$return['time'] = microtime(true) - $time_start;
			// self::$queries[] = $return;
			self::$query_count++;
			self::$transactions[] = $return;
		//	return results
			return $return;
		}
	//	Errors
		catch(Exception $e)
		{
		//	cancel transaction
			$this->_pdo->rollback();
		//	erreor message
			$l = array(
				'Message' => $e->getMessage(),
				'Database' => self::$env,
				//'Line' => $e->getFile().' '.$e->getLine(),
	    		'What went wrong ?' => 'Sorry, <strong>'.$query.'</strong> is not a valid request.',
				'Params' => print_r($p, true)
		    );
		   	sentinel::log(E_ERROR, $l);
		}
	}
/**
 * Return the total of executed queries on database::query()
 *
 * @return	int		query count
 * @access	public
 * @static
 */
	public static function query_count()
	{
		return self::$query_count;
	}
	
/**
 * Return la liste des tables de la bdd
 *
 * @param	string	l'environnement désiré. "admin" ou "site"
 * @return	array	la liste des tables
 * @access	public
 * @static
 */
	public static function get_tables($env = env)
	{
		if (!$tables = registry::get($env, registry::dbtable_index))
		{
			$q = 'SHOW TABLES';
			$db = self::connect($env);
			$stmt = $db->_pdo->query($q);
			while ($row = $stmt->fetch(PDO::FETCH_NUM))
			{
				$tables[] = $row[0];
			}
			registry::set($env, registry::dbtable_index, $tables);
		}
		
		return $tables;
	}
	
/**
 * Query database for items data
 *
 * @param	string	l'environnement désiré. "admin" ou "site"
 * @param	string	la table
 * @param	array	les paramètres de recherche
 * @param	bool	active le select count
 * @return	array	les résultats
 * @access	public
 * @static
 */
	public static function query_item($env, $table, $params = null, $counter = false)
	{
	//	préparation des variables
		$i = 0;
		$cWhere = null;
		$cOrder = null;
		$cLimit = null;
		$preparedData = array();
	//	Construction des clauses de la query
		if (isset($params['order()']))
		{
			$order = $params['order()'];
			unset($params['order()']);
		}
		if (isset($params['limit()']))
		{
			$limit = $params['limit()'];
			unset($params['limit()']);
		}
	//	recherche de la définition des attributs dans le registre
		// print'<pre>';print_r($params);print'</pre>';
		$attrs = registry::get($env, registry::structure_index, $table, 'attr');
		if (empty($attrs)) trigger_error('Sorry, can\'t find the structure of <strong>'.$table.'</strong>.', E_USER_ERROR);
		$attrsKey = array_keys($attrs);
		$rels = array();
		// print'<pre>';print_r($attrs);print'</pre>';
		foreach ((array) $params as $key => $value)
		{
			if (isset($attrs[$key]) && $attrs[$key]['type'] == 'rel')
			{
				$rels[$key] = $value;
				unset($params[$key]);
			}
		}
	//	clause Where
		foreach ((array) $params as $key => $param)
		{
			$cOr = null;
			foreach ((array) $param as $value)
			{
			//	opérateur
				switch ($value)
				{
					case false:
						$operator = '=';
						break;
					case mb_strpos($value, '<'):
					case mb_strpos($value, '>'):
					case mb_strpos($value, '!'):
						preg_match('/([<>!=]+)\s*(.*)/', $value, $matches);
						$operator = $matches[1];
						$value = $matches[2];
						break;
					case in_array($attrs[$key]['type'], array('string', 'date', 'array')):
						$operator = 'LIKE';
						break;
					default:
						$operator = '=';
						break;
				}
			//	OR or AND
				if (in_array($operator, array('>', '<', '<=', '>=', '!=')))
				{
					$concat = 'AND';
				}
				else
				{
					$concat = 'OR';
				}
			//	clause OR
				$cOr .= '`'.$table.'`.`'.$key.'` '.$operator.' :'.$key.$i.' '.$concat.' ';
				$preparedData[$key.$i] = $value;
				$i++;
			}
			$cWhere .= '('.substr($cOr, 0, -4).') AND ';
		}
		if ($i > 0) $cWhere = ' WHERE '.substr($cWhere, 0, -5);
	//	clause Relations
		$i = 0;
		$cRel = null;
		$cJoin = null;
		$cGroupby = null;
		$tableRel = attrRel::table;
		foreach ((array) $rels as $key => $rel)
		{
			$cKey = '`'.$tableRel.'`.`key` = "'.$key.'" AND `'.$tableRel.'`.`relid` IN';
			$cIn = null;
			foreach ((array) $rel as $value)
			{
				$cIn .= ':relid'.$i.',';
				$preparedData['relid'.$i] = mb_substr($value, mb_strpos($value, '_') + 1);
				$i++;
			}
			$cRel .= '('.$cKey.' ('.substr($cIn, 0, -1).')) OR ';
		}
		if ($i > 0)
		{
			$cJoin = ' JOIN `'.$tableRel.'` ON (`'.$table.'`.`id` = `'.$tableRel.'`.`itemid` AND `'.$tableRel.'`.`item` = "'.$table.'") AND ('.substr($cRel, 0, -4).')';
			$cGroupby = ' GROUP BY `'.$table.'`.`id` HAVING COUNT(`'.$table.'`.`id`) = '.$i.' ';
		}
	//	clause Order By
		if (isset($order))
		{
		//	tableau à traiter
			$orders = preg_split('/[,]\s*/', $order);
		//	inherit
			for ($e=0, $count=count($orders); $e < $count; $e++)
			{
				if (preg_match('/inherit\((.*)\)/', $orders[$e], $matches))
				{
					if (isset($params[$matches[1]]))
					{
						$field = '"'.implode('","', $params[$matches[1]]).'"';
						$orders[$e] = str_replace($matches[0], 'FIELD('.$matches[1].','.$field.')', $orders[$e]);
					}
				}
			}
		//	on traduit proprement
			$func = function($value)
			{
				return '/\b('.$value.')\b/i';
			};
			$orders = preg_replace(array_map($func, $attrsKey),'`'.$table.'`.`$1`', $orders);
		//	écriture de la clause order by
			$cOrder = ' ORDER BY '.implode(', ', $orders).'';
		}
	//	clause Limit
		if (isset($limit))
		{
			$cLimit = ' LIMIT '.$limit;
		}
	//	écriture de la requête
		if ($counter === false)
		{
			$preparedQuery = 'SELECT `'.$table.'`.* FROM `'.$table.'`'.$cJoin.$cWhere.$cGroupby.$cOrder.$cLimit;
		}
		else
		{
			$preparedQuery = 'SELECT COUNT(*) as count FROM `'.$table.'`'.$cJoin.$cWhere.$cGroupby.$cLimit;
		}
	//	requête
		$db = database::connect($env);
		$results = $db->query($preparedQuery, $preparedData);
	//	créations d'un item vide
		$model = array();
		foreach ($attrs as $key => $value)
		{
			if ($attrs[$key]['type'] == 'rel')
			{
				$value['env'] = $env;
			}
			$attrClass = 'attr'.ucfirst($attrs[$key]['type']);
			$model[$key] = new $attrClass(null, $value);
		}
		// print'<pre>';print_r($model);print'</pre>';
	//	création du tableau de retour
		$datas = array();
		$ids = array();
	//	si SELECT
		if ($counter === false)
		{
			foreach ($results['data'] as $data)
			{
			//	copie du modèle
				foreach ($model as $k => $v)
				{
					$tmp[$k] = clone $v;
				}
			//	affectation des valeurs
				foreach ($data as $key => $value)
				{
					$tmp[$key]->database_set($value);
				}
				$id = $tmp['id']->get();
				$datas[$id] = $tmp;
				$ids[] = $id;
			}
		//	recherche des relations
			if (count($ids) > 0)
			{
				$relationQuery = 'SELECT * FROM `'.attrRel::table.'` WHERE `item` = "'.$table.'" AND `itemid` IN ('.implode(',', $ids).') ORDER BY position';
				$results = $db->query($relationQuery);
			//	affectation des résultats
				foreach ($results['data'] as $value)
				{
					if (isset($datas[$value['itemid']][$value['key']]))
					{
						$datas[$value['itemid']][$value['key']]->add($value['rel'].'_'.$value['relid']);
					}
				}
			}
		}
	//	si SELECT COUNT
		else
		{
			$datas['count'] = (empty($cGroupby)) ? $results['data'][0]['count'] : count($results['data']);
		}
		return array_values($datas);
	}
	
/**
 * Query database for items data
 *
 * @param	string	l'environnement désiré. "admin" ou "site"
 * @param	string	la table
 * @param	array	les paramètres de recherche
 * @param	bool	active le select count
 * @return	array	les résultats
 * @access	public
 * @static
 */
	public function spool($query, $params = null, $lastid = false)
	{
		$query = array(
			'query' => $query,
			'params' => $params,
		);
		if ($lastid)
		{
			$query['lastid'] = true;
		}
		$this->_spooler[] = $query;
	}
	
/**
 * Query database for items data
 *
 * @param	string	l'environnement désiré. "admin" ou "site"
 * @param	string	la table
 * @param	array	les paramètres de recherche
 * @param	bool	active le select count
 * @return	array	les résultats
 * @access	public
 * @static
 */
	public function flush_spooler()
	{
		// print'<pre>';print_r($this->_spooler);print'</pre>';exit;
		try
		{
		//	begin transaction
			$this->_pdo->beginTransaction();
		//	Préparation
			for ($i=0, $count=count($this->_spooler); $i < $count; $i++)
			{ 
			//	prepare query
				$stmt = $this->_pdo->prepare($this->_spooler[$i]['query']);
			//	inject data et execute query
				$stmt->execute($this->_spooler[$i]['params']);
			//	last inserted id
				if (isset($this->_spooler[$i]['lastid']) && $this->_spooler[$i]['lastid'] === true)
				{
					$id = $this->_pdo->lastInsertId();
					// print'<pre>';print_r($id);print'</pre>';
					foreach ($this->_spooler[$i+1]['params'] as $key => $value)
					{
						if ($value === 'lastid')
						{
							$this->_spooler[$i+1]['params'][$key] = $id;
						}
					}
				}
			}
		//	commit
			$this->_pdo->commit();
		//	free memory
			unset($stmt);
		//	log
			self::$query_count += count($this->_spooler);
			self::$transactions[] = $this->_spooler;
			$this->_spooler = null;
		//	return
			if (isset($id)) return $id;
		}
	//	Errors
		catch(Exception $e)
		{
		//	cancel transaction
			$this->_pdo->rollback();
		//	erreor message
			$l = array(
				'Message' => $e->getMessage(),
				'Database' => self::$env,
				//'Line' => $e->getFile().' '.$e->getLine(),
	    		'What went wrong ?' => 'Sorry, <strong>'.print_r($this->_spooler, true).'</strong> is not a valid transaction.',
		    );
		   	sentinel::log(E_ERROR, $l);
		}
	}
}
?>