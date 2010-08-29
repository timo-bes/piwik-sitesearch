<?php

/**
 * Site Search Plugin
 * Archive
 *
 * Author:   Timo Besenreuther
 *           EZdesign.de
 * Created:  2010-07-23
 * Modified: 2010-08-29
 */

class Piwik_SiteSearch_Archive {
	
	/* Archive indexes */
    const INDEX_SUM_HITS = 2;
    const INDEX_SUM_UNIQUE_HITS = 3;
    // 4 corresponds to Piwik_Archive::INDEX_MAX_ACTIONS and will therefore
    // not be summed but maximized (and every row has the same value).
    const INDEX_RESULTS = 4;
    
    /** Archive index to property name mapping */
    public static $indexToNameMapping = array(
		self::INDEX_SUM_HITS => 'hits',
        self::INDEX_SUM_UNIQUE_HITS => 'unique_hits',
        self::INDEX_RESULTS => 'results'
    );
    
    /* Current archive processing variables */
	private $idsite;
	private $site;
	private $period;
	private $startDate;
	private $endDate;
	
	/** Current archive processing object
	 * @var Piwik_ArchiveProcessing */
	private $archiveProcessing;
	
	private static $instance;
	/** Get singleton instance
	 * @return Piwik_SiteSerach_Archive */
	public static function getInstance() {
		if (self::$instance == null) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	/** Get data table from archive
     * @return Piwik_DataTable */
    public static function getDataTable($name, $idsite, $period, $date, $numeric=false) {
    	Piwik::checkUserHasViewAccess($idsite);
		
    	if (is_array($name)) {
    		foreach ($name as &$col) {
    			$col = 'SiteSearch_'.$col;
    		}
    	} else {
    		$name = 'SiteSearch_'.$name;
    	}
    	
		if (!is_string($period) && get_class($period) != 'Piwik_Period_Range') {
			$periodMap = array(
				'Piwik_Period_Day' => 'day',
				'Piwik_Period_Week' => 'week',
				'Piwik_Period_Month' => 'month',
				'Piwik_Period_Year' => 'year'
			);
			$period = $periodMap[get_class($period)];
		}
		
		$archive = Piwik_Archive::build($idsite, $period, $date);
		if ($numeric) {
			$dataTable = $archive->getDataTableFromNumeric($name);
			$dataTable->queueFilter('ReplaceColumnNames', array(false, array(
				'SiteSearch_totalSearches' => 'totalSearches',
				'SiteSearch_visitsWithSearches' => 'visitsWithSearches'
			)));
            $dataTable->applyQueuedFilters();
		} else {
			$dataTable = $archive->getDataTable($name);
			$dataTable->queueFilter('ReplaceColumnNames',
					array(false, self::$indexToNameMapping));
            $dataTable->applyQueuedFilters();
		}

		return $dataTable;
    }
    
    /** Extend logging of an action */
    public static function logAction($action, $idSite, $site, $resultCount=false) {
    	$parameter = $site['sitesearch_parameter'];
    	$hit = preg_match('/'.$parameter.'=(.*?)(&|$)/i', $action['name'], $match);
		if ($hit) {
			$searchTerm = strtolower(urldecode($match[1]));
			$id = self::getSearchTermId($searchTerm, $idSite, $resultCount);
			$bind = array(':searchTerm' => $id);
			Piwik_Query('
				UPDATE '.Piwik_Common::prefixTable('log_action').'
				SET search_term = :searchTerm
				WHERE idaction = '.intval($action['idaction']).'
			', $bind);
		}
    }
    
	/** Get search term id (select or insert) */
	private static function getSearchTermId($searchTerm, $idSite, $resultCount) {
		$searchTerm = utf8_encode(strtolower(trim($searchTerm)));
		$bind = array(':searchTerm' => $searchTerm, 'idSite' => intval($idSite));
		
		$sql = '
			SELECT id, results
			FROM '.Piwik_Common::prefixTable('log_sitesearch').'
			WHERE search_term = :searchTerm AND idsite = :idSite
		';
		$row = Piwik_FetchRow($sql, $bind);
		if ($row) {
			if ($resultCount !== false && $resultCount != $row['results']) {
				// update results count
				$sql = '
					UPDATE '.Piwik_Common::prefixTable('log_sitesearch').'
					SET results = '.intval($resultCount).'
					WHERE search_term = :searchTerm AND idsite = :idSite
				';
				Piwik_Query($sql, $bind);
			}
			return intval($row['id']);
		}
		
		$bind[':results'] = intval($resultCount);
		$sql = '
			INSERT INTO '.Piwik_Common::prefixTable('log_sitesearch').'
			(search_term, idsite, results) VALUES (:searchTerm, :idSite, :results)
		';
		Piwik_Query($sql, $bind);
		
		return Piwik_FetchOne('SELECT LAST_INSERT_ID() AS id');
	}
	
	/** Build archive for a single day */
	public static function archiveDay(Piwik_ArchiveProcessing $archive) {
		$self = self::getInstance();
		$self->extractArchiveProcessing($archive);
		if (empty($self->site['sitesearch_url']) ||
				empty($self->site['sitesearch_parameter'])) {
			return;
		}
		
		$self->dayAnalyzeKeywords();
		$self->dayAnalyzeAssociatedPages(true);
		$self->dayAnalyzeAssociatedPages(false);
		$self->dayAnalyzeNumberOfSearches();
	}
	
	/** Build archive for a period */
	public static function archivePeriod(Piwik_ArchiveProcessing $archive) {
		$self = self::getInstance();
		$self->extractArchiveProcessing($archive);
		if (empty($self->site['sitesearch_url']) ||
				empty($self->site['sitesearch_parameter'])) {
			return;
		}
		
		$archive->archiveDataTable(array(
			'SiteSearch_keywords',
			'SiteSearch_noResults',
			'SiteSearch_followingPages',
			'SiteSearch_previousPages'
		));
		
		$archive->archiveNumericValuesSum(array(
			'SiteSearch_totalSearches',
			'SiteSearch_visitsWithSearches'
		));
	}
	
	/** Extract values from ArchiveProcessing */
	private function extractArchiveProcessing(Piwik_ArchiveProcessing $archive) {
		$this->archiveProcessing = $archive;
		$this->idsite = intval($archive->idsite);
		$this->site = Piwik_SitesManager_API::getInstance()->getSiteFromId($this->idsite);
		$this->period = $archive->period;
		$this->startDate = $archive->getStartDatetimeUTC();
		$this->endDate = $archive->getEndDatetimeUTC();
	}
	
	/** Get basic sql bindings */
	private function getSqlBindings() {
		return array(
			':idsite' => $this->idsite,
			':startDate' => $this->startDate,
			':endDate' => $this->endDate
		);
	}
	
	/** Get base URL of site */
	private function getSiteUrlBase() {
		$url = $this->site['main_url'];
		if (substr($url, -1) == '/') {
			$url = substr($url, 0, -1);
		}
		return $url;
	}
	
	/**
	 * Analyze searches
	 * - number of visits with searches
	 * - number of total searches
	 */
	private function dayAnalyzeNumberOfSearches() {
		$query = '
			SELECT
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
				visit.idsite = :idsite AND
				action.search_term IS NOT NULL AND
				(visit_first_action_time BETWEEN :startDate AND :endDate)
		';
		$result = Piwik_FetchRow($query, $this->getSqlBindings());
		
		$this->archiveProcessing->insertNumericRecord(
				'SiteSearch_totalSearches', $result['searches']);
		$this->archiveProcessing->insertNumericRecord(
				'SiteSearch_visitsWithSearches', $result['visits']);
	}
	
	/**
	 * Analyze keywords
	 * SiteSearch_keywords: archive grouped by keywords
	 * SiteSearch_noResults: archive grouped by keywords with no results
	 */
	private function dayAnalyzeKeywords() {
		$sql = '
			SELECT
				search.id AS id_search,
				search.search_term AS label,
				search.results AS `'.self::INDEX_RESULTS.'`,
				COUNT(action.idaction) AS `'.self::INDEX_SUM_HITS.'`,
				COUNT(DISTINCT visit.idvisit) AS `'.self::INDEX_SUM_UNIQUE_HITS.'`
			FROM
				'.Piwik_Common::prefixTable('log_visit').' AS visit
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_link_visit_action').' AS visit_action
				ON visit.idvisit = visit_action.idvisit
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_action').' AS action
				ON action.idaction = visit_action.idaction_url
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_sitesearch').' AS search
				ON action.search_term = search.id
			WHERE
				visit.idsite = :idsite AND
				action.search_term IS NOT NULL AND
				(visit_first_action_time BETWEEN :startDate AND :endDate)
			GROUP BY
				search.id
		';
		
		$keywordsData = Piwik_FetchAll($sql, $this->getSqlBindings());
		
		$noResultsData = array();
		foreach ($keywordsData as &$search) {
			if (!$search[self::INDEX_RESULTS]) {
				$noResultsData[] = $search;
			}
		}
		
		$this->archiveDataArray('keywords', $keywordsData, true);
		$this->archiveDataArray('noResults', $noResultsData);
	}
	
	/**
	 * Analyze pages associated with the search and stores them in a single
	 * DataTable for all keywords.
	 */
	private function dayAnalyzeAssociatedPages($following) {
		if ($following) {
			// pages following a search
			$getAction = 'idaction_url';
			$setAction = 'idaction_url_ref';
		} else {
			// pages before a search
			$getAction = 'idaction_url_ref';
			$setAction = 'idaction_url';
		}
		
		$bind = $this->getSqlBindings();
		$bind[':url'] = $this->getSiteUrlBase();
		
		$sql = '
			SELECT
				CONCAT(search.id, "_", action_get.idaction) AS label,
				search.id AS id_search,
				REPLACE(action_get.name, :url, "") AS page,
				COUNT(action_get.idaction) AS `'.self::INDEX_SUM_HITS.'`
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
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_sitesearch').' AS search
				ON action_set.search_term = search.id
			WHERE
				visit.idsite = :idsite AND
				visit_action.idaction_url_ref != 0 AND
				action_set.search_term IS NOT NULL AND
			    action_get.search_term IS NULL AND
				(visit.visit_server_date BETWEEN :startDate AND :endDate)
			GROUP BY
				search.id,
				action_get.idaction
		';
		
		$data = Piwik_FetchAll($sql, $bind);
		
		$name = ($following ? 'following' : 'previous').'Pages';
		return $this->archiveDataArray($name, $data);
	}
	
	/**
	 * Build DataTable from array and archive it
	 * @return id of the datatable
	 */
	private function archiveDataArray($keyword, &$data, $addSearchTermMetaData=false) {
		$dataTable = new Piwik_DataTable();
		foreach ($data as &$row) {
			$rowData = array(Piwik_DataTable_Row::COLUMNS => $row);
			if ($addSearchTermMetaData) {
				$rowData[Piwik_DataTable_Row::METADATA] = array(
					'id_search' => $row['id_search'],
					'search_term' => $row['label']
				);
			}
			$dataTable->addRow(new Piwik_DataTable_Row($rowData));
		}
		
		$id = $dataTable->getId();
		$name = 'SiteSearch_'.$keyword;
		$this->archiveProcessing->insertBlobRecord($name, $dataTable->getSerialized());
		destroy($dataTable);
		
		return $id;
	}
	
}