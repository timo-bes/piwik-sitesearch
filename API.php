<?php

/**
 * Site Search Plugin
 * API
 *
 * Author:   Timo Besenreuther
 *           EZdesign.de
 * Created:  2010-07-17
 * Modified: 2010-07-17
 */

class Piwik_SiteSearch_API {
	
	static private $instance = null;
	
	/** Get singleton instance
	 * @return Piwik_SiteSearch_API */
	static public function getInstance() {
		if (self::$instance == null) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	/** Get a site configuration */
	private function getSite($idSite) {
		return Piwik_SitesManager_API::getInstance()->getSiteFromId($idSite);
	}
	
	/** Get the most popular search keywords
	 * @return Piwik_DataTable */
	public function getSearchKeywords($idSite) {
		Piwik::checkUserHasViewAccess($idSite);
		
		$site = $this->getSite($idSite);
		$table = new Piwik_DataTable();
		$searchViews = $this->loadSearchViews($site);
		foreach ($searchViews as &$searchView) {
			$searchView['label'] = self::extractKeyword($searchView['label'], $site['sitesearch_parameter']);
			$table->addRow(new Piwik_DataTable_Row(array(
				Piwik_DataTable_Row::COLUMNS => $searchView,
				Piwik_DataTable_Row::METADATA => array('idaction' => $searchView['idaction'])
			)));
		}
		
		return $table;
	}
	
	/** Extract keyword from URL */
	public static function extractKeyword($url, $parameter) {
		$hit = preg_match('/'.$parameter.'=(.*?)(&|$)/i', $url, $match);
		if ($hit) {
			$label = urldecode($match[1]);
		} else {
			$label = '(error)';
		}
		return $label;
	}
	
	/** Get information about search access */
	private function loadSearchViews($site) {
		$url = $site['main_url'];
		if (substr($url, -1) != '/') {
			$url .= '/';
		}
		$url .= $site['sitesearch_url'];
		
		$sql = '
			SELECT
				action.idaction,
				action.name AS label,
				COUNT(action.idaction) AS hits
			FROM
				'.Piwik_Common::prefixTable('log_visit').' AS visit
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_link_visit_action').' AS visit_action
				ON visit.idvisit = visit_action.idvisit
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_action').' AS action
				ON action.idaction = visit_action.idaction_url
			WHERE
				visit.idsite = '.intval($site['idsite']).' AND
				action.type = 1 AND
				action.name LIKE "'.mysql_real_escape_string($url).'%"
			GROUP BY
				action.idaction
		';
		return Piwik_FetchAll($sql);
	}
	
	/** Get the next sites after keyword was searched
	 * @return Piwik_DataTable */
	public function getKeywordPages($idSite, $idaction) {
		Piwik::checkUserHasViewAccess($idSite);
		
		$site = $this->getSite($idSite);
		$table = new Piwik_DataTable();
		$searchViews = $this->loadKeywordPages($site, $idaction);
		foreach ($searchViews as &$searchView) {
			$table->addRow(new Piwik_DataTable_Row(array(
				Piwik_DataTable_Row::COLUMNS => $searchView
			)));
		}
		
		return $table;
	}
	
	/** Get information about the next site after keyword was searched */
	private function loadKeywordPages($site, $idaction) {
		$sql = '
			SELECT
				action.idaction,
				action.name AS label,
				COUNT(action.idaction) AS hits
			FROM
				'.Piwik_Common::prefixTable('log_action').' AS action
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_link_visit_action').' AS visit_action
				ON action.idaction = visit_action.idaction_url
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_visit').' AS visit
				ON visit.idvisit = visit_action.idvisit
			WHERE
				visit.idsite = '.intval($site['idsite']).' AND
				visit_action.idaction_url_ref = '.intval($idaction).'
			GROUP BY
				action.idaction
		';
		return Piwik_FetchAll($sql);
	}
	
}
