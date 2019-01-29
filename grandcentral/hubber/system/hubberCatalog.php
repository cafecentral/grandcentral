<?php
/**
 * Boite à outil pour la synchronisation du catalogue Hubber
 *
 * @package  Hubber
 * @author   Sylvain Frigui <sf@cafecentral.fr>
 * @access   public
 * @see      http://www.cafecentral.fr/fr/wiki
 */
class hubberCatalog
{
  private $xml;
  private $catalog;

/**
 * Récupérer le catalogue
 *
 * @return	bunch  Retourne le bunch des séances de l'événement
 * @access	public
 */
	public function save_xml($path)
	{
    $dir = new dir($path);
    if (!$dir->exists())
    {
      $dir->save();
    }

    $file = new file($path.'/catalog-'.date('Y-z-U').'.xml');
    $file->set($this->xml);
    $file->save();
	}

/**
 * Récupérer le xml du catalogue
 *
 * @access	public
 */
  public function parse_url($url, $user, $pass, $catalog = 'billets')
	{
    $this->catalog = explode(',',mb_strtolower($catalog));
    for ($i=0, $count=count($this->catalog) ; $i < $count; $i++) $this->catalog[$i] = trim($this->catalog[$i]);
    $timeout = 15;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_FAILONERROR,0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

    if (preg_match('`^https://`i', $url))
    {
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    }

    if (!empty($user) && !empty($pass))
    {
      curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
      curl_setopt($ch, CURLOPT_USERPWD, $user.':'.$pass);
    }

    $this->xml = curl_exec($ch);
    curl_close($ch);
	}

/**
 * Récupérer le xml du catalogue
 *
 * @access	public
 */
  public function parse_xml()
	{
    libxml_use_internal_errors(TRUE);
    $dom = new DomDocument();
    $dom->loadXML($this->xml);
    $errors = libxml_get_errors();
    // error
    if (count($errors) > 0)
    {
      echo "<pre>";print_r($errors);echo "</pre>";
      exit;
    }
    // parse
    else
    {
      $catalogs = $dom->getElementsByTagName('SPECTACLE');

      foreach ($catalogs as $catalog)
      {
        $title = $catalog->getElementsByTagName('TITLE')[0]->nodeValue;
        $title = mb_strtolower(trim($title));
        // echo "<pre>";print_r($title);echo "</pre>";
        // if (mb_strstr(mb_strtolower($title), 'saison') !== false)
        // if (mb_strtolower(trim($title)) == 'billets')
        // if (trim($title) == 'COMEDIEFRANCAISE7')
        // if (mb_strstr(mb_strtolower($title), 'billets hors carte') !== false)
        // echo "<pre>";print_r($title);echo "</pre>";
        // if (mb_strtolower(trim($title)) == 'billets 18-19')
        if (in_array($title, $this->catalog))
        {
          // echo "<pre>";print_r($title);echo "</pre>";
          echo '<strong>Catalogue : '.$title.'</strong><br>';
          $data = $this->_parse_catalog($catalog);
          $this->_save_catalog($data);
        }
      }
    }
	}
/**
 * Parser une saison
 *
 * @access	private
 */
  private function _parse_catalog(DOMElement $catalog)
	{
    $data = [];
    $data['id'] = $catalog->getElementsByTagName('ID')[0]->nodeValue;
    $data['title'] = preg_replace('/[^0-9-]/', '', $catalog->getElementsByTagName('TITLE')[0]->nodeValue);
    $events = $catalog->getElementsByTagName('MANIFESTATION');
    foreach ($events as $event)
    {
      $data['event'][] = $this->_parse_event($event);
    }
    return $data;
  }

/**
 * Parser un event
 *
 * @access	private
 */
  private function _parse_event(DOMElement $event)
	{
    $data = [];
    $id = $event->getElementsByTagName('ID');
    $url = $event->getElementsByTagName('URL');
    $title = $event->getElementsByTagName('TITLE');
    $place = $event->getElementsByTagName('PLACE');
    $place_id = $event->getElementsByTagName('PLACE_ID');
    $date_debut = $event->getElementsByTagName('DATE_DEBUT');
    $date_fin = $event->getElementsByTagName('DATE_FIN');
    $visuel = $event->getElementsByTagName('VISUEL');
    $description_fr = $event->getElementsByTagName('DESCRIPTION_FR');
    $description_en = $event->getElementsByTagName('DESCRIPTION_EN');
    $short_description_fr = $event->getElementsByTagName('SHORT_DESCRIPTION_FR');
    $short_description_en = $event->getElementsByTagName('SHORT_DESCRIPTION_EN');
    $ordre = $event->getElementsByTagName('ORDRE');
    $prix_min = $event->getElementsByTagName('PRICE_MIN');
    $prix_max = $event->getElementsByTagName('PRICE_MAX');
    $status = $event->getElementsByTagName('STATUS');
    $status_id = $event->getElementsByTagName('STATUS_ID');
    $sell_status = $event->getElementsByTagName('SELL_STATUS');
    $sell_status_id = $event->getElementsByTagName('SELL_STATUS_ID');

    $data['id'] = $id->item(0)->nodeValue;
    $data['url'] = $url->item(0)->nodeValue;
    $data['title'] = array(
      'fr' => trim(html_entity_decode($title->item(0)->nodeValue, ENT_XML1, 'UTF-8')),
      'en' => trim(html_entity_decode($title->item(0)->nodeValue, ENT_XML1, 'UTF-8'))
    );
    $data['place'] = trim($place->item(0)->nodeValue);
    $data['place_id'] = $place_id->item(0)->nodeValue;
    // $data['date_debut'] = $date_debut->item(0)->nodeValue;
    $data['date_debut'] = '9999-99-99 99:99:99';
    // $data['date_fin'] = $date_fin->item(0)->nodeValue;
    $data['date_fin'] = '0000-00-00 00:00:00';
    $data['description'] = array(
      'fr' => trim(html_entity_decode($description_fr->item(0)->nodeValue, ENT_XML1, 'UTF-8')),
      'en' => trim(html_entity_decode($description_en->item(0)->nodeValue, ENT_XML1, 'UTF-8'))
    );
    $data['short_description'] = array(
      'fr' => trim(html_entity_decode($short_description_fr->item(0)->nodeValue, ENT_XML1, 'UTF-8')),
      'en' => trim(html_entity_decode($short_description_en->item(0)->nodeValue, ENT_XML1, 'UTF-8'))
    );
    $data['ordre'] = $ordre->item(0)->nodeValue;
    $data['prix_min'] = $prix_min->item(0)->nodeValue;
    $data['prix_max'] = $prix_max->item(0)->nodeValue;
    $data['status'] = $status->item(0)->nodeValue;
    $data['status_id'] = $status_id->item(0)->nodeValue;
    $data['sell_status'] = $sell_status->item(0)->nodeValue;
    $data['sell_status_id'] = $sell_status_id->item(0)->nodeValue;

    $seances = $event->getElementsByTagName('MEETING');
    foreach ($seances as $seance)
    {
      $tmp = $this->_parse_seance($seance);
      // push only seance with publication = 1
      if ($tmp['publication'] == 1)
      {
        $data['seance'][] = $tmp;
      }
    }
    // order seance by date
    // ksort($data['seance']);
    // $data['seance'] = array_values($data['seance']);

    // hack : get start and end date from seance instead of xml feed
    if (!empty($data['seance']))
    {
      $data['date_debut'] = $data['seance'][0]['date'];
      $data['date_fin'] = $data['seance'][(count($data['seance']) - 1)]['date'];
    }
    else
    {
      $data['date_debut'] = $date_debut->item(0)->nodeValue;
      $data['date_fin'] = $date_fin->item(0)->nodeValue;
    }

    // echo "<pre>event : ";print_r($data);echo "</pre>";
    // $data['url'] = $data['seance'][0]['url'];

    return $data;
  }
/**
 * Parser une seance
 *
 * @access	private
 */
  private function _parse_seance(DOMElement $seance)
	{
    $data = [];
    $id = $seance->getElementsByTagName('NID');
    $url = $seance->getElementsByTagName('URL');
    $publication = $seance->getElementsByTagName('PUBLICATION');
    $date = $seance->getElementsByTagName('DATE');
    $status = $seance->getElementsByTagName('STATUT');
    $maxplace = $seance->getElementsByTagName('MAXPLACE');
    $date_vente = $seance->getElementsByTagName('DATE_VENTE');

    $data['id'] = $id->item(0)->nodeValue;
    $data['url'] = $url->item(0)->nodeValue;
    $data['publication'] = $publication->item(0)->nodeValue;
    $data['date'] = $date->item(0)->nodeValue;
    $data['status'] = $status->item(0)->nodeValue;
    $data['maxplace'] = $maxplace->item(0)->nodeValue;
    $data['date_vente'] = $date_vente->item(0)->nodeValue;

    $categories = $seance->getElementsByTagName('CATEGORIE');
    foreach ($categories as $category)
    {
      $data['category'][] = $this->_parse_category($category);
    }

    $data['pricemin'] = $this->_get_minprice($data);
    $data['pricemax'] = $this->_get_maxprice($data);

    return $data;
  }
/**
 * Parser une categorie de prix
 *
 * @access	private
 */
  private function _parse_category(DOMElement $category)
	{
    $data = [];
    $id = $category->getElementsByTagName('NID');
    $title = $category->getElementsByTagName('TITLE');
    $data['id'] = $id->item(0)->nodeValue;
    $data['title'] = $title->item(0)->nodeValue;

    $prices = $category->getElementsByTagName('PRICE');
    foreach ($prices as $price)
    {
      $data['price'][] = $this->_parse_price($price);
    }
    return $data;
  }
/**
 * Parser un prix
 *
 * @access	private
 */
  private function _parse_price(DOMElement $price)
	{
    $title = $price->getElementsByTagName('TITLE');
    $amount = $price->getElementsByTagName('AMOUNT');
    $data = array(
      'title' => $title->item(0)->nodeValue,
      'amount' => $amount->item(0)->nodeValue
    );
    return $data;
  }

/**
 * Sauvegarder une saison
 *
 * @access	private
 */
  private function _save_catalog($data)
	{
    // $season = i('season');
    // $season->get(array(
    //   'externalid' => $data['id']
    // ));
    //
    // if (!$season->exists())
    // {
    //   $season['externalid'] = $data['id'];
    //   $season['title'] = $data['title'];
    //   // $season->save();
    // }
    //
    // echo "<pre>";print_r($data);echo "</pre>";exit;
    foreach ($data['event'] as $event)
    {
      $this->_save_event($event);
    }

  }
/**
 * Sauvegarder un event
 *
 * @access	private
 */
  private function _save_event($data)
	{
    $q = 'SELECT id, title FROM season WHERE start <= "'.mb_substr($data['date_debut'], 0, 10).'" AND end >= "'.mb_substr($data['date_debut'], 0, 10).'" LIMIT 1';
    $db = database::connect('site');
    $r = $db->query($q);
    // echo "<pre>";print_r($r);echo "</pre>";
    if ($r['count'] > 0)
    {
      $event = i('event');
      $event->get(array(
        'externalid' => $data['id'],
        'status' => ['live','asleep']
      ));
      // echo "<pre>";print_r($data);echo "</pre>";
      $event['season'] = 'season_'.$r['data'][0]['id'];
      $event['externalid'] = $data['id'];
      $event['externalurl'] = $data['url'];
      if ($event['title']->is_empty()) $event['title'] = $data['title'];
      if ($event['descr']->is_empty()) $event['descr'] = $data['description'];
      $event['salestart'] = $this->_get_salestart($data);
      $event['pricemin'] = $data['prix_min'];
      $event['pricemax'] = $data['prix_max'];
      $event['place'] = $this->_get_place($data['place']);
      $event['status'] = $this->_get_status($data['status_id']);
      // on ne change pas l'état des spectacles bientôt disponibles (https://trello.com/c/E9SqtEuH)
      if ($event['sellstatus']->is_empty() || $event['sellstatus']->get() != 'sellstatus_4') {$event['sellstatus'] = $this->_get_sellstatus($data['sell_status']);}
      if (isset($data['seance']))
      {
        $seances = $this->_get_seances($event, $data['seance']);
        // echo "<pre>";print_r($seances);echo "</pre>";
        $event['seance'] = json_encode($seances, JSON_UNESCAPED_UNICODE);//json_encode($data['seance']);
        if ($event['start']->is_empty()) $event['start'] = $seances[0]['date'];
        if ($event['end']->is_empty()) $event['end'] = $seances[(count($data['seance']) - 1)]['date'];
      }
      else {
        if ($event['start']->is_empty()) $event['start'] = $data['date_debut'];
        if ($event['end']->is_empty()) $event['end'] = $data['date_fin'];
      }
      if ($event['saleend']->is_empty()) $event['saleend'] = $data['date_fin'];

      // if ($event['id']->get() == 1322)
      // {
      //   // echo "<pre>";print_r($event);echo "</pre>";
      //   echo "<pre>";print_r($data);echo "</pre>";
      // }
      $event->save();
      echo $event['id'].' / '.$event['title'].' : saved ('.$r['data'][0]['title'].')<br>';
    }
  }
/**
 * Sauvegarder une séance
 *
 * @access	private
 */
  private function _get_place($place)
	{
    $item = i('place',$place);
    if ($item->exists())
    {
      return $item->get_nickname();
    }
    return null;
  }
/**
 * Sauvegarder une séance
 *
 * @access	private
 */
  private function _get_seances($event, $rawseances)
	{
    $seances = [];
    // echo "<pre>";print_r($event);echo "</pre>";
    // if ($event['id']->get() == 1322)
    // {
    foreach (json_decode($event['seance']->get(), true) as $seance)
    {
      $seances[$seance['date']] = $seance;
    }
    foreach ($rawseances as $seance)
    {
      $seances[$seance['date']] = $seance;
    }
    // echo "<pre>";print_r($seances);echo "</pre>";
    ksort($seances);
    $seances = array_values($seances);
    // $seances = json_encode($seances);
    // echo "<pre>";print_r(count($seances));echo "</pre>";

    // echo "<pre>ancien : ";print_r(count(json_decode($event['seance']->get(), true)));echo "</pre>";
    // echo "<pre>nouveau : ";print_r($rawseances);echo "</pre>";
    // }
    return $seances;
  }
/**
 * Sauvegarder une séance
 *
 * @access	private
 */
  private function _get_salestart($event)
	{
    if (!isset($event['seance'])) return '2001-01-01 00:00:00';
    $start = new DateTime('now');
    foreach ($event['seance'] as $seance)
    {
      $date = new DateTime($seance['date_vente']);
      if ($start > $date)
      {
        $start = $date;
      }
    }
    return $start->format('Y-m-d h:i:s');
  }
/**
 * Obtenir le status de l'event
 *
 * @access	private
 */
  private function _get_status($statusId)
	{
    // return $statusId == 1 ? 'live' : 'asleep';
    return 'live';
  }
/**
 * Obtenir le status de venter
 *
 * @access	private
 */
  private function _get_sellstatus($status)
	{
    $item = i('sellstatus',$status);
    if ($item->exists())
    {
      return $item->get_nickname();
    }
    return null;
  }
/**
 * Obtenir le status de l'event
 *
 * @access	private
 */
  private function _get_minprice($data)
	{
    $minprice = null;
    if (isset($data['category']))
    {
      foreach ($data['category'] as $category)
      {
        foreach ($category['price'] as $price)
        {
          if (is_null($minprice) || $price['amount'] < $minprice)
          {
            $minprice = $price['amount'];
          }
        }
      }
    }
    return $minprice;
  }
/**
 * Obtenir le status de l'event
 *
 * @access	private
 */
  private function _get_maxprice($data)
	{
    $maxprice = null;
    if (isset($data['category']))
    {
      foreach ($data['category'] as $category)
      {
        foreach ($category['price'] as $price)
        {
          if (is_null($maxprice) || $price['amount'] > $maxprice)
          {
            $maxprice = $price['amount'];
          }
        }
      }
    }
    return $maxprice;
  }
}
?>
