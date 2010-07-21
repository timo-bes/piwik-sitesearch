<?php

/**
 * Site Search Plugin
 * API
 *
 * Author:   Timo Besenreuther
 *           EZdesign.de
 * Created:  2010-07-17
 * Modified: 2010-07-21
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
	
	/** Convert date to sql ready string */
	private function convertDate($date) {
		return Piwik_Date::factory($date)->toString();
	}
	
	/** Returns period object
	 * @return Piwik_Period */
	private function getPeriod($date, $period) {
		return Piwik_Period::factory($period, Piwik_Date::factory($date));
	}
	
	/** Get the most popular search keywords
	 * @return Piwik_DataTable */
	public function getSearchKeywords($idSite, $period, $date) {
		Piwik::checkUserHasViewAccess($idSite);
		
		$table = new Piwik_DataTable();
		$period = $this->getPeriod($date, $period);
		$searchViews = $this->loadSearchViews($idSite, $period);
		foreach ($searchViews as &$searchView) {
			$table->addRow(new Piwik_DataTable_Row(array(
				Piwik_DataTable_Row::COLUMNS => $searchView,
				Piwik_DataTable_Row::METADATA => array(
					'search_term' => $searchView['label']
				)
			)));
		}
		
		return $table;
	}
	
	/** Get information about search access */
	private function loadSearchViews($idSite, Piwik_Period $period) {
		$sql = '
			SELECT
				action.idaction,
				action.search_term AS label,
				action.search_results AS results,
				COUNT(action.idaction) AS hits,
				COUNT(DISTINCT visit.idvisit) AS unique_hits
			FROM
				'.Piwik_Common::prefixTable('log_visit').' AS visit
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_link_visit_action').' AS visit_action
				ON visit.idvisit = visit_action.idvisit
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_action').' AS action
				ON action.idaction = visit_action.idaction_url
			WHERE
				visit.idsite = '.intval($idSite['idsite']).' AND
				action.type = 1 AND
				action.search_term IS NOT NULL AND
				(visit.visit_server_date BETWEEN "'.$period->getDateStart().'" AND "'.$period->getDateEnd().'")
			GROUP BY
				action.search_term
		';
		return Piwik_FetchAll($sql);
	}
	
	/** Get the next sites after keyword was searched
	 * @return Piwik_DataTable */
	public function getFollowingPages($idSite, $period, $date) {
		$searchTerm = Piwik_Common::getRequestVar('search_term', false);
		return $this->getAssociatedPages($idSite, true, $searchTerm, $period, $date);
	}
	
	/** Get the next sites before keyword was searched
	 * @return Piwik_DataTable */
	public function getPreviousPages($idSite, $period, $date) {
		$searchTerm = Piwik_Common::getRequestVar('search_term', false);
		return $this->getAssociatedPages($idSite, false, $searchTerm, $period, $date);
	}
	
	/** Get table containing informatino about associated pages
	 * @return Piwik_DataTable */
	private function getAssociatedPages($idSite, $following, $searchTerm, $period, $date) {
		Piwik::checkUserHasViewAccess($idSite);
		
		$site = $this->getSite($idSite);
		$table = new Piwik_DataTable();
		$period = $this->getPeriod($date, $period);
		$searchViews = $this->loadAssociatedPages($site, $following, $searchTerm, $period);
		
		foreach ($searchViews as &$searchView) {
			$table->addRow(new Piwik_DataTable_Row(array(
				Piwik_DataTable_Row::COLUMNS => $searchView
			)));
		}
		
		return $table;
	}
	
	/**
	 * Get information about pages associated with the search
	 * following=true, searchTerm=false: all pages that were visited after a search
	 * following=true, searchTerm=x: pages that were after seraching for a certain keyword
	 * following=false, searchTerm=false: pages searches started from
	 */
	private function loadAssociatedPages($site, $following, $searchTerm, Piwik_Period $period) {
		if ($following) {
			// pages following a search
			$getAction = 'idaction_url';
			$setAction = 'idaction_url_ref';
		} else {
			// pages before a search
			$getAction = 'idaction_url_ref';
			$setAction = 'idaction_url';
		}
		
		if ($searchTerm) {
			// analyze one search term
			$searchTerm = mysql_escape_string($searchTerm);
			$where = 'AND action_set.search_term = "'.$searchTerm.'" '
			       . 'AND action_get.search_term != "'.$searchTerm.'" ';
		} else {
			// analyze all keywords
			$where = 'AND action_set.search_term IS NOT NULL';
		}
		
		$url = $site['main_url'];
		if (substr($url, -1) == '/') {
			$url = substr($url, 0, -1);
		}
		
		$sql = '
			SELECT
				action_get.idaction,
				REPLACE(action_get.name, "'.mysql_escape_string($url).'", "") AS label,
				COUNT(action_get.idaction) AS hits
			FROM
				'.Piwik_Common::prefixTable('log_action').' AS action_set
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_link_visit_action').' AS visit_action
				ON action_set.idaction = visit_action.'.$setAction.'
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_action').' AS action_get
				ON action_get.idaction = visit_action.'.$getAction.'
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_visit').' AS visit
				ON visit.idvisit = visit_action.idvisit
			WHERE
				visit.idsite = '.intval($site['idsite']).' AND
				visit_action.idaction_url_ref != 0 AND
				(visit.visit_server_date BETWEEN "'.$period->getDateStart().'" AND "'.$period->getDateEnd().'")
				'.$where.'
			GROUP BY
				action_get.idaction
		';
		return Piwik_FetchAll($sql);
	}
	
}
