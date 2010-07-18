<?php

/**
 * Site Search Plugin
 * API
 *
 * Author:   Timo Besenreuther
 *           EZdesign.de
 * Created:  2010-07-17
 * Modified: 2010-07-18
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
			$label = '(unknown)';
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
				action.search_results AS results,
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
				action.name LIKE "'.mysql_escape_string($url).'%"
			GROUP BY
				action.idaction
		';
		return Piwik_FetchAll($sql);
	}
	
	/** Get the next sites after keyword was searched
	 * @return Piwik_DataTable */
	public function getFollowingPages($idSite, $idaction=false) {
		return $this->getAssociatedPages($idSite, true, $idaction);
	}
	
	/** Get the next sites before keyword was searched
	 * @return Piwik_DataTable */
	public function getPreviousPages($idSite, $idaction=false) {
		return $this->getAssociatedPages($idSite, false, $idaction);
	}
	
	/** Get table containing informatino about associated pages
	 * @return Piwik_DataTable */
	private function getAssociatedPages($idSite, $following, $idaction) {
		Piwik::checkUserHasViewAccess($idSite);
		
		$site = $this->getSite($idSite);
		$table = new Piwik_DataTable();
		$searchViews = $this->loadAssociatedPages($site, $following, $idaction);
		
		foreach ($searchViews as &$searchView) {
			$table->addRow(new Piwik_DataTable_Row(array(
				Piwik_DataTable_Row::COLUMNS => $searchView
			)));
		}
		
		return $table;
	}
	
	/**
	 * Get information about pages associated with the search
	 * following=true, idaction=false: all pages that were visited after a search
	 * following=true, idaction=x: pages that were after seraching for a certain keyword
	 * following=false, idaction=false: pages searches started from
	 */
	private function loadAssociatedPages($site, $following, $idaction) {
		if ($following) {
			// pages following a search
			$getAction = 'idaction_url';
			$setAction = 'idaction_url_ref';
		} else {
			// pages before a search
			$getAction = 'idaction_url_ref';
			$setAction = 'idaction_url';
		}
		
		if ($idaction) {
			// analyze one keyword
			$where = 'AND visit_action.'.$setAction.' = '.intval($idaction);
		} else {
			// analyze all keywords
			// TODO: where clause checking site search url
			$where = 'AND FALSE';
		}
		
		$url = $site['main_url'];
		if (substr($url, -1) == '/') {
			$url = substr($url, 0, -1);
		}
		
		$sql = '
			SELECT
				action.idaction,
				REPLACE(action.name, "'.mysql_escape_string($url).'", "") AS label,
				COUNT(action.idaction) AS hits
			FROM
				'.Piwik_Common::prefixTable('log_action').' AS action
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_link_visit_action').' AS visit_action
				ON action.idaction = visit_action.'.$getAction.'
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_visit').' AS visit
				ON visit.idvisit = visit_action.idvisit
			WHERE
				visit.idsite = '.intval($site['idsite']).' AND
				visit_action.idaction_url_ref != 0
				'.$where.'
			GROUP BY
				action.idaction
		';
		return Piwik_FetchAll($sql);
	}
	
}
