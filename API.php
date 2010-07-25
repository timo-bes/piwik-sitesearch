<?php

/**
 * Site Search Plugin
 * API
 *
 * Author:   Timo Besenreuther
 *           EZdesign.de
 * Created:  2010-07-17
 * Modified: 2010-07-25
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
	
	/** Get evolution of search
	 * @return Piwik_DataTable*/
	public function getSearchEvolution($idSite, $period, $date) {
		switch ($period) {
		case 'year':
			$period = 'month';
			break;
		case 'month':
			$period = 'week';
			break;
		case 'week':
			$period = 'day';
			break;
		case 'day':
		default:
			break;
		}
		
		$period = new Piwik_Period_Range($period, 'last12');
		$dateStart = $period->getDateStart()->toString();
		$dateEnd = $period->getDateEnd()->toString();
		$searchTerm = Piwik_Common::getRequestVar('search_term', false);
		
		$where = '';
		$bind = array();
		if ($searchTerm) {
			$where = 'AND action.search_term = :searchTerm';
			$bind[':searchTerm'] = $searchTerm;
		}
		
		// TODO: exclude multiple result pages from totalSearches
		// check, whether previous action had the same keyword
		$query = '
			SELECT
				visit.visit_server_date AS date,
				COUNT(action.idaction) AS searches,
				COUNT(DISTINCT visit.idvisit) AS visits
			FROM
				'.Piwik_Common::prefixTable('log_visit').' AS visit
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_link_visit_action').' AS visit_action
				ON visit.idvisit = visit_action.idvisit
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_action').' AS action
				ON action.idaction = visit_action.idaction_url
			WHERE
				visit.idsite = '.intval($idSite).' AND
				action.type = 1 AND
				action.search_term IS NOT NULL AND
				(visit.visit_server_date BETWEEN "'.$dateStart.'" AND "'.$dateEnd.'")
				'.$where.'
			GROUP BY
				visit.visit_server_date
		';
		$result = Piwik_FetchAll($query, $bind);
		
		$dataTable = new Piwik_DataTable();
		$data = array();
		$i = 0;
		foreach ($period->getSubperiods() as $subPeriod) {
			$dateStart = $subPeriod->getDateStart();
			$dateEnd = $subPeriod->getDateEnd();
			$visits = 0;
			$searches = 0;
			while (isset($result[$i]) && $result[$i]['date'] <= $dateEnd) {
				$visits += $result[$i]['visits'];
				$searches += $result[$i]['searches'];
				$i++;
			}
			$data[$subPeriod->getLocalizedShortString()] = array(
				'visitsWithSearches' => $visits,
				'totalSearches' => $searches
			);
		}
		
		$dataTable->addRowsFromArrayWithIndexLabel($data);
		return $dataTable;
	}
	
	/** Get the most popular search keywords
	 * @return Piwik_DataTable */
	public function getSearchKeywords($idSite, $period, $date) {
		Piwik::checkUserHasViewAccess($idSite);
		$period = $this->getPeriod($date, $period);
		return Piwik_SiteSearch_Archive::getDataTable(
				'keywords', $idSite, $period, $date);
	}
	
	/** Get keywords without search results
	 * @return Piwik_DataTable */
	public function getNoResults($idSite, $period, $date) {
		Piwik::checkUserHasViewAccess($idSite);
		$period = $this->getPeriod($date, $period);
		return Piwik_SiteSearch_Archive::getDataTable(
				'noResults', $idSite, $period, $date);
	}
	
	/** Get the next sites after keyword was searched
	 * @return Piwik_DataTable */
	public function getFollowingPages($idSite, $period, $date) {
		return $this->getAssociatedPages($idSite, true, $period, $date);
	}
	
	/** Get the next sites before keyword was searched
	 * @return Piwik_DataTable */
	public function getPreviousPages($idSite, $period, $date) {
		return $this->getAssociatedPages($idSite, false, $period, $date);
	}
	
	/** Get table containing informatino about associated pages
	 * @return Piwik_DataTable */
	private function getAssociatedPages($idSite, $following, $period, $date) {
		Piwik::checkUserHasViewAccess($idSite);
		
		$searchTerm = Piwik_Common::getRequestVar('search_term', false);
		$dataTableId = intval(Piwik_Common::getRequestVar('dataTable', 0));
		
		$name = ($following ? 'following' : 'previous').'Pages';
		if (!$searchTerm) {
			return Piwik_SiteSearch_Archive::getDataTable($name, $idSite, $period, $date);
		} else if (!$dataTableId) {
			return null;
		} else {
			$name .= '_'.$dataTableId;
			return Piwik_SiteSearch_Archive::getDataTable($name, $idSite, $period, $date);
		}
	}

    /** Get search refinements
	 * @return Piwik_DataTable */
	public function getSearchRefinements($idSite, $period, $date) {
		$searchTerm = Piwik_Common::getRequestVar('search_term', false);
		$data = $this->getSearchRefinementsArray($searchTerm);

        $table = new Piwik_DataTable();
        foreach ($data as $keyword => $hits) {
			$table->addRow(new Piwik_DataTable_Row(array(
				Piwik_DataTable_Row::COLUMNS => compact('keyword', 'hits')
			)));
		}
        return $table;
	}

    /** Get search refinements as array */
    private function getSearchRefinementsArray($keyword) {

        // TODO: limit period

		// get all searches for the keyword
		// if a keyword was searched multiple times within one visit,
		// follow the first one (keyword will appear in refinements)
		$sql = '
			SELECT
				link.idvisit,
				MIN(link.idlink_va) AS idlink
			FROM
				'.Piwik_Common::prefixTable('log_link_visit_action').' AS link
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_action').' AS action
				ON link.idaction_url_ref = action.idaction
			WHERE
				action.search_term = :searchTerm
			GROUP BY
				link.idvisit
		';
		// follow each visit individually
		$searches = Piwik_FetchAll($sql, array(':searchTerm' => $keyword));
		$results = array();
		foreach ($searches as $search) {
			$this->followVisitFindRefinements($search['idlink'], $results);
		}
		return $results;
	}

    /** Follow a visit and find search refinements */
	private function followVisitFindRefinements($idlink, &$results) {
		// check whether link is a search
		// find next idlink
		$sql = '
			SELECT
				action.search_term,
				MIN(next_link.idlink_va) AS next_idlink
			FROM
				'.Piwik_Common::prefixTable('log_link_visit_action').' AS link
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_action').' AS action
				ON link.idaction_url = action.idaction
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_link_visit_action').' AS next_link
				ON link.idaction_url = next_link.idaction_url_ref
				AND link.idvisit = next_link.idvisit
				AND next_link.idlink_va > link.idlink_va
			WHERE
				link.idlink_va = :idlink
			GROUP BY
				next_link.idaction_url_ref,
				action.search_term
		';
		$next = Piwik_FetchAll($sql, array(':idlink' => $idlink));
		if (count($next)) {
			$next = $next[0];

			// build result
			if (!empty($next['search_term'])) {
				$term = $next['search_term'];
				if (isset($results[$term])) {
					$results[$term]++;
				} else {
					$results[$term] = 1;
				}
			}

			// recurse
			$idlink = intval($next['next_idlink']);
			if ($idlink) {
				$this->followVisitFindRefinements($idlink, $results);
			}
		}
	}
	
}