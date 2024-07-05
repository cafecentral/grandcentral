<?php
/**
* TDC Event object
*
* @package  Core
* @author   Sylvain Frigui <sf@cafecentral.fr>
* @access   public
* @see      http://www.cafecentral.fr/fr/wiki
*/
class itemEvent extends _items
{
  public $tagrel;
  public $sync_status;
  public $sync_status_fetched = false;
  /**
	* Obtenir les artistes associé à l'événement
	*
	* @return	string  Retourne une chaine de caractères
	* @access	public
	*/
	// public function get_artist($field = 'casting') {
  //   $fielddata = $this[$field];
  //   $roles = [];
  //   foreach ($fielddata as $data)
  //   {
  //     preg_match('/\d+/', $data['artist'], $idArray);
  //     $id = $idArray[0];
  //
  //     $roles[$id] = [
  //       'nickname' => 'artist_'.$id,
  //       'role' => new attrI18n($data['role'])
  //     ];
  //   }
  //
  //   $artists = i('artist', [
  //     'id' => array_keys($roles),
  //     'order()' => 'inherit(id)',
  //     'status' => ['live','asleep']
  //   ])->set_index('id');
  //
  //   foreach ($roles as $role)
  //   {
  //     $artist = $artists[$role['nickname']];
  //     $artist['role'] = $role['role'];
  //     $artists[$role['nickname']] = $artist;
  //   }
  //
  //   return $artists;
  // }
  public function get_artist($field = 'casting') {
    $fieldData = isset($this[$field]) && !$this[$field]->is_empty() ? $this[$field] : array();
    $ids = [];
    $datas = [];
    foreach ($fieldData as $data) {
      preg_match('/\[(\d*)\]/', $data['artist'], $idArray);
      if (!empty($idArray)) {
        $ids[] = $idArray[1];
        $datas['artist_'.$idArray[1]] = $data;
      }
      else {
        $datas[] = $data;
      }
    }

    if (!empty($ids)) {
      $artists = i('artist', [
        'id' => $ids,
        'status' => array('live','asleep'),
        'order()' => 'inherit(id)',
        ])->set_index('id');

        foreach ($artists as $artist) {
          $nickname = $artist->get_nickname();
          if (isset($datas[$nickname])) {
            $datas[$nickname]['artist'] = $artist->get_display_name();
            $datas[$nickname]['url'] = $artist['url'];
            $datas[$nickname]['imagepush'] = $artist['imagepush'];
            $datas[$nickname]['status'] = $artist['status'];
          }
        }
    }

    return $datas;
  }

  private function is_sync_status_active_from_feed() {
    if (!$this->sync_status_fetched) {
      $sync_page = i('page', 'synchubber');
      $this->sync_status = (bool) $sync_page['type']['master']['param']['sync_status'];
      $this->sync_status_fetched = true;

      $sellstatus = i('sellstatus', all);
      $this->sellstatus = $sellstatus->set_index('id');
    }
    return $this->sync_status;
  }

  /**
	* Obtenir les séances de l'événement
	*
	* @return	string  Retourne une chaine de caractères
	* @access	public
	*/
	public function get_seance() {
    $seances = json_decode($this['seance']->get(), true);
    // HACK force session status if Disable status sync in synchubber page param is active
    if ($this->is_sync_status_active_from_feed()) {
      for ($i=0, $count = count($seances); $i < $count; $i++)
      {
        $seances[$i]['status'] = $this->sellstatus[$this['sellstatus']->get()]['key']->get();
      }
    }
    return $seances;
  }

  public static function get_seance_date($date, $nbDays, $diff = '') {
		$dateNow = is_string($date) ? new DateTime($date) : $date;
		$dateFirst = is_string($diff) && $diff != '' ? $dateNow->modify($diff) : clone $dateNow;
		$dateTemp = clone $dateFirst;
		$data = [];

		$db = database::connect('site');
		$q = 'SELECT `title`, `shortdescr`, `start`, `end`, `seance`, `place`, `url` FROM `event` WHERE `status` = "live" AND (';
		$where = [];
		for ($i=0; $i < $nbDays; $i++)
		{
			$where[] = '(`start` <= "'.$dateTemp->format('Y-m-d').' 23:59:59" AND `end` >= "'.$dateTemp->format('Y-m-d').'")';
      $where[] = '(`start` LIKE "%'.$dateTemp->format('Y-m-d').'%" AND `end` LIKE "%'.$dateTemp->format('Y-m-d').'%")';
			$data[$dateTemp->format('Y-m-d')] = [];
			$dateTemp->modify('+1 day');
		}
		$q .= implode(' OR ', $where).')';
    // echo "<pre>";print_r($q);echo "</pre>";
		$results = $db->query($q);

		foreach ($results['data'] as $value)
		{
			$event = i('event');
			$event['seance'] = $value['seance'];
			$event['title'] = json_decode($value['title'], true);
			$event['shortdescr'] = json_decode($value['shortdescr'], true);
      $event['place'] = $value['place'];
      $event['url'] = json_decode($value['url'], true);
			$seances = $event->get_seance();
      // echo "<pre>";print_r($seances);echo "</pre>";
			if (!empty($seances)) {
        foreach ($seances as $seance) {
  				unset($seance['category']);
  				$dateSeance = new DateTime($seance['date']);
          $hour = $dateSeance->format('H:i');
  				$dateSeance->setTime(0, 0, 0);
          $place = i($event['place']);

  				$seance['title'] = (string) $event['title'];
  				$seance['descr'] = (string) $event['shortdescr'];
  				$seance['place'] = (string) $place['title'];
  				$seance['button'] = trim(get_snippet('content', '_snippet/button/button-status', ['seance' => $seance]));
          $seance['event_url'] = (string) $event['url'];

  				if (isset($data[$dateSeance->format('Y-m-d')])) {
  					$data[$dateSeance->format('Y-m-d')][$hour.'_'.$seance['id']] = $seance;
  				}
  			}
			}
		}

		ksort($data);

    foreach ($data as $key => $l)
    {
      ksort($l);
      $data[$key] = array_values($l);
    }

    return $data;
	}

  public static function get_history_event($date, $nbDays, $diff = '') {
		$dateNow = is_string($date) ? new DateTime($date) : $date;
		$dateFirst = is_string($diff) && $diff != '' ? $dateNow->modify($diff) : clone $dateNow;
		$dateTemp = clone $dateFirst;
		$data = [];

		$db = database::connect('site');
		$q = 'SELECT `title`, `text`, `date`, `imagepush`, `legend` FROM `historyevent` WHERE ';
		$where = [];
		for ($i=0; $i < $nbDays; $i++)
		{
			$where[] = '`date` LIKE "%-' . $dateTemp->format('m-d') . '%"';
			$dateTemp->modify('+1 day');
		}
		$q .= implode(' OR ', $where);
		$results = $db->query($q);

    foreach ($results['data'] as $key => $value) {
      $historyEvent = [];
      $event = i('historyevent');
      $event['title'] = json_decode($value['title'], true);
      $event['text'] = json_decode($value['text'], true);
      $event['legend'] = json_decode($value['legend'], true);
      $mediaObject = json_decode($value['imagepush'], true);
      $event['imagepush'] = media($mediaObject[0]['url']);

      $historyEvent['title'] = (string) $event['title'];
      $historyEvent['text'] = (string) $event['text'];
      $historyEvent['legend'] = (string) $event['legend'];
      $historyEvent['date'] = (string) $value['date'];
      $historyEvent['image'] = isset($event['imagepush']) && !$event['imagepush']->is_empty() ? (string) $event['imagepush']->unfold()[0]->crop(600, 600) : '';
      $data[] = $historyEvent;
    }

    return $data;
	}

  public function get_rel_by_tag($tables = [])
  {

    if ($this['tag']->is_empty()) return [];

    $tables = (array) $tables;

    if (empty($this->tagrel))
    {
      $tagIds = [];
      foreach ($this['tag'] as $nickname)
      {
        $t = explode('_', $nickname);
        $tagIds[] = $t[1];
      }

      $q = 'SELECT * FROM _rel WHERE rel = "tag" AND item != "event" AND relid IN ('.implode(',',$tagIds).')';
      $db = database::connect('site');
      $r = $db->query($q);
      $datas = [];
      foreach ($r['data'] as $rel)
      {
        if (!empty($tables) && in_array($rel['item'], $tables))
        {
          $datas[$rel['item']][$rel['itemid']] = isset($datas[$rel['item']][$rel['itemid']]) ? $datas[$rel['item']][$rel['itemid']] + 1 : 1;
        }

      }
      $this->tagrel = $datas;
    }
    return $this->tagrel;
  }

  public function get_gallery() {
    $medias = new bunch();

    $rels = $this->get_rel_by_tag('media');
    if (isset($rels['media']))
    {
      $ids = array_keys($rels['media']);
      $medias->get('media', [
        'id' => $ids,
        'type' => array('image','video'),
        'order()' => 'created DESC',
        'limit()' => 12
      ]);
    }
    return $medias;
  }

  public function get_news() {
    $news = new bunch();
    $tables = array('media','news');
    $rels = $this->get_rel_by_tag($tables);

    foreach ($tables as $table)
    {
      if (isset($rels[$table]))
      {
        $p = [
          'id' => array_keys($rels['media']),
          'order()' => 'created DESC',
          'limit()' => 12
        ];
        if ($table == 'media') $p['type'] = 'pdf';
        $news->get($table, $p);
      }
    }
    return $news;
  }

  /**
 * Obtenir la date formatée pour l'affichage
 *
 * @return	string  la date formatée
 * @access	public
 */
	public function get_formated_date($for = false)
	{
    // echo "<pre>";print_r($this);echo "</pre>";
		switch (true)
		{
      // pas de date remplie
			case $this['start']->is_empty() && $this['end']->is_empty():
				$return = null;
				break;
    // si date de départ et de fin avec année différentes
      case !$this['start']->is_empty() && !$this['end']->is_empty() && $this['start']->format('Y') != $this['end']->format('Y'):
        $sday = $this['start']->format('j') == '1' ? $this['start']->format('j').cst('DATE_DAY_FIRST') : $this['start']->format('j');
        $smonth = $this['start']->format('n') == $this['end']->format('n') ? null : cst('MONTH_'.$this['start']->format('n'));
        $eday = $this['end']->format('j') == '1' ? $this['end']->format('j').cst('DATE_DAY_FIRST') : $this['end']->format('j');
        $return = cst('EVENT_BOX_DATE_'.($this['blurstart']->get() === true ? 'BLUR' : '').'START_DATE_'.($this['blurend']->get() === true ? 'BLUR' : '').'END', array(
          'sday' => $this['blurstart']->get() === false ? $sday : '',
          'smonth' => $smonth,
          'syear' => $this['start']->format('Y'),
          'eday' => $this['blurend']->get() === false ? $eday : '',
          'emonth' => cst('MONTH_'.$this['end']->format('n')),
          'eyear' => $this['end']->format('Y')
        ));
        break;
      // si date de départ uniquement ou si jour et mois de la date de début de et fin correspondent
			case !$this['start']->is_empty() && $this['end']->is_empty():
			case !$this['start']->is_empty() && !$this['end']->is_empty() && $this['start']->format('dn') == $this['end']->format('dn'):
				$sday = $this['start']->format('j') == '1' ? $this['start']->format('j').cst('DATE_DAY_FIRST') : $this['start']->format('j');
        $hour = $this['start']->format('i') == '00' ? $this['start']->format('H\h') : $this['start']->format('H\hi');
				$return = cst('EVENT_BOX_DATE_'.($this['blurstart']->get() === true ? 'BLUR' : '').'START', array(
					'sday' => $this['blurstart']->get() === false ? $sday : '',
					'smonth' => cst('MONTH_'.$this['start']->format('n')),
					'year' => cst($this['end']->format('Y')),
          'hour' => '&nbsp;'.$hour
				));
				break;
			default:
				$sday = $this['start']->format('j') == '1' ? $this['start']->format('j').cst('DATE_DAY_FIRST') : $this['start']->format('j');
				$smonth = $this['start']->format('n') == $this['end']->format('n') ? null : cst('MONTH_'.$this['start']->format('n'));
				$eday = $this['end']->format('j') == '1' ? $this['end']->format('j').cst('DATE_DAY_FIRST') : $this['end']->format('j');
        if ($for === 'cover') {
          $return = cst('EVENT_BOX_DATE_END_COVER', array(
            'sday' => $this['blurstart']->get() === false ? $sday : '',
            'smonth' => $smonth,
            'eday' => $this['blurend']->get() === false ? $eday : '',
            'emonth' => cst('MONTH_'.$this['end']->format('n')),
            'year' => $this['end']->format('Y')
          ));
        }
        else {
          $return = cst('EVENT_BOX_DATE_'.($this['blurstart']->get() === true ? 'BLUR' : '').'START_DATE_'.($this['blurend']->get() === true ? 'BLUR' : '').'END', array(
            'sday' => $this['blurstart']->get() === false ? $sday : '',
            'smonth' => $smonth,
            'syear' => '',
            'eday' => $this['blurend']->get() === false ? $eday : '',
            'emonth' => cst('MONTH_'.$this['end']->format('n')),
            'eyear' => $this['end']->format('Y')
          ));
        }
				break;
		}
		return $return;
	}
}
?>
