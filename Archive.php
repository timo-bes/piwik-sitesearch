<?php

/**
 * Piwik - Open source web analytics
 * SiteSearch Plugin
 * Archive
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @author Timo Besenreuther, EZdesign.de
 *
 * @category Piwik_Plugins
 * @package Piwik_SiteSearch
 */

class Piwik_SiteSearch_Archive {
	
	/* Archive indexes */
    const HITS = 2;
    const UNIQUE_HITS = 3;
    const RESULTS = 4;
    const SEARCH_TERM = 5;
    const SEARCH_TERM_ID = 6;
    const SEARCH_TERM_ID_2 = 7;
    const PAGE = 8;
    const URL = 9;
    const LABEL = 'label';
    
    /** The columns that should be summed when archiving */
    public static $columnsToSum = array(
    	self::HITS,
    	self::UNIQUE_HITS
    );
    
    /** The indexes that should be the latest value */
    public static $columnsToTakeLatest = array(
    	self::RESULTS
    );
    
    /** Columns traslations */
    private static $columnTranslations = array(
    	self::HITS => 'SiteSearch_Hits',
    	self::UNIQUE_HITS => 'SiteSearch_UniqueHits',
	    self::RESULTS => 'SiteSearch_Results',
	    self::SEARCH_TERM => 'SiteSearch_Keyword',
	    self::LABEL => 'SiteSearch_Keyword',
	    self::PAGE => 'SiteSearch_Page'
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
	 * @return Piwik_SiteSearch_Archive */
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
			// numeric archives are only used for search evolution
			$dataTable = $archive->getDataTableFromNumeric($name);
			$dataTable->queueFilter('ReplaceColumnNames', array(array(
				'SiteSearch_totalSearches' => self::HITS,
				'SiteSearch_visitsWithSearches' => self::UNIQUE_HITS
			)));
            $dataTable->applyQueuedFilters();
		} else {
			$dataTable = $archive->getDataTable($name);
		}
		
		return $dataTable;
    }
    
    /** Translate a column */
    public static function displayColumns($view, $columns) {
    	foreach ($columns as $column) {
	    	$view->setColumnTranslation($column,
	    			Piwik_Translate(self::$columnTranslations[$column]));
    	}
    	$view->setColumnsToDisplay($columns);
    }
    
    /** Extend logging of an action */
    public static function logAction($action, $idSite, $site, $resultCount=false) {
    	$parameter = $site['sitesearch_parameter'];
    	$regex = '/(&|\?)'.preg_quote($parameter, '/').'=(.*?)(&|$)/i';
    	
    	$hit = preg_match($regex, $action['name'], $match);
    	
		if ($hit) {
			$searchTerm = strtolower(urldecode($match[2]));
			
			$id = self::getSearchTermId($searchTerm, $idSite, $resultCount);
			$bind = array(':searchTerm' => $id);
			Piwik_SiteSearch_Db::query('
				UPDATE '.Piwik_Common::prefixTable('log_action').'
				SET search_term = :searchTerm
				WHERE idaction = '.intval($action['idaction']).'
			', $bind);
		}
    }
    
    /** Get search term id (select or insert) */
	private static function getSearchTermId($searchTerm, $idSite, $resultCount) {
		$searchTerm = utf8_encode(strtolower(trim($searchTerm)));
		$bind = array(':searchTerm' => $searchTerm, ':idSite' => intval($idSite));
		
		$sql = '
			SELECT id, results
			FROM '.Piwik_Common::prefixTable('log_sitesearch').'
			WHERE search_term = :searchTerm AND idsite = :idSite
		';
		$row = Piwik_SiteSearch_Db::fetchRow($sql, $bind);
		if ($row) {
			if ($resultCount !== false && $resultCount != $row['results']) {
				// update results count
				$sql = '
					UPDATE '.Piwik_Common::prefixTable('log_sitesearch').'
					SET results = '.intval($resultCount).'
					WHERE search_term = :searchTerm AND idsite = :idSite
				';
				Piwik_SiteSearch_Db::query($sql, $bind);
			}
			return intval($row['id']);
		}
		
		$bind[':results'] = intval($resultCount);
		$sql = '
			INSERT INTO '.Piwik_Common::prefixTable('log_sitesearch').'
			(search_term, idsite, results) VALUES (:searchTerm, :idSite, :results)
		';
		Piwik_SiteSearch_Db::query($sql, $bind);
		
		return Piwik_SiteSearch_Db::fetchOne('SELECT LAST_INSERT_ID() AS id');
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
		$self->dayAnalyzeRefinements();
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
			'SiteSearch_previousPages',
			'SiteSearch_refinements'
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
		$result = Piwik_SiteSearch_Db::fetchRow($query, $this->getSqlBindings());
		
		$this->archiveProcessing->insertNumericRecord(
				'SiteSearch_totalSearches', $result['searches']);
		$this->archiveProcessing->insertNumericRecord(
				'SiteSearch_visitsWithSearches', $result['visits']);
	}
	
	/**
	 * Analyze refinements
	 * Find out which terms visitors also searched for
	 */
	private function dayAnalyzeRefinements() {
		$sql = '
			SELECT
				CONCAT(action_from.search_term, "_", action_to.search_term)
						AS `'.self::LABEL.'`,
				action_from.search_term AS `'.self::SEARCH_TERM_ID.'`,
				action_to.search_term AS `'.self::SEARCH_TERM_ID_2.'`,
				search.search_term AS `'.self::SEARCH_TERM.'`,
				search.results AS `'.self::RESULTS.'`,
				COUNT(DISTINCT visit.idvisit) AS `'.self::UNIQUE_HITS.'`
			FROM
				'.Piwik_Common::prefixTable('log_visit').' AS visit
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_link_visit_action').' AS visit_action_from
				ON visit.idvisit = visit_action_from.idvisit
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_action').' AS action_from
				ON action_from.idaction = visit_action_from.idaction_url
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_link_visit_action').' AS visit_action_to
				ON visit_action_from.idvisit = visit_action_to.idvisit
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_action').' AS action_to
				ON action_to.idaction = visit_action_to.idaction_url
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_sitesearch').' AS search
				ON action_to.search_term = search.id
			WHERE
				visit.idsite = :idsite AND
				(visit.visit_first_action_time BETWEEN :startDate AND :endDate) AND
				action_from.search_term IS NOT NULL AND
				action_to.search_term IS NOT NULL AND
				action_from.search_term != action_to.search_term AND
				search.search_term != ""
			GROUP BY
				action_from.search_term,
				action_to.search_term
		';
		
		$refinements = Piwik_SiteSearch_Db::fetchAll($sql, $this->getSqlBindings());
		$this->archiveDataArray('refinements', $refinements, self::SEARCH_TERM_ID_2);
	}
	
	/**
	 * Analyze keywords
	 * SiteSearch_keywords: archive grouped by keywords
	 * SiteSearch_noResults: archive grouped by keywords with no results
	 */
	private function dayAnalyzeKeywords() {
		$sql = '
			SELECT
				search.id AS `'.self::LABEL.'`,
				search.id AS `'.self::SEARCH_TERM_ID.'`,
				search.search_term AS `'.self::SEARCH_TERM.'`,
				search.results AS `'.self::RESULTS.'`,
				COUNT(action.idaction) AS `'.self::HITS.'`,
				COUNT(DISTINCT visit.idvisit) AS `'.self::UNIQUE_HITS.'`
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
				(visit_first_action_time BETWEEN :startDate AND :endDate) AND
				search.search_term != ""
			GROUP BY
				search.id
		';
		
		$keywordsData = Piwik_SiteSearch_Db::fetchAll($sql, $this->getSqlBindings());
		
		$noResultsData = array();
		foreach ($keywordsData as &$search) {
			if (!$search[self::RESULTS]) {
				$noResultsData[] = $search;
			}
		}
		
		$this->archiveDataArray('keywords', $keywordsData, self::LABEL);
		$this->archiveDataArray('noResults', $noResultsData, self::LABEL);
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
		
		// version class might not be available in archiving or tracker
		require_once PIWIK_INCLUDE_PATH .'/core/Version.php';
		
		// check whether version is prior to 1.2, adjust sql query
		$version = explode('.', Piwik_Version::VERSION);
		$pre12 = $version[0] < 1 || $version[1] < 2;
		if ($pre12) {
			$dateRange = '(visit.visit_server_date BETWEEN :startDate AND :endDate)';
		} else {
			$dateRange = '(visit_action.server_time BETWEEN :startDate AND :endDate)';
		}
		
		$sql = '
			SELECT
				CONCAT(search.id, "_", action_get.idaction) AS `'.self::LABEL.'`,
				search.id AS `'.self::SEARCH_TERM_ID.'`,
				REPLACE(action_get.name, :url, "") AS `'.self::PAGE.'`,
				action_get.name AS `'.self::URL.'`,
				COUNT(action_get.idaction) AS `'.self::HITS.'`
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
				'.$dateRange.'
			GROUP BY
				search.id,
				action_get.idaction
		';
		
		$data = Piwik_SiteSearch_Db::fetchAll($sql, $bind);
		
		$name = ($following ? 'following' : 'previous').'Pages';
		return $this->archiveDataArray($name, $data, false, true);
	}
	
	/**
	 * Build DataTable from array and archive it
	 * @return id of the datatable
	 */
	private function archiveDataArray($keyword, &$data, $addSearchTermMetaData=false,
			$addUrlMetaData=false) {
		$dataTable = new Piwik_DataTable();
		foreach ($data as &$row) {
			$rowData = array(Piwik_DataTable_Row::COLUMNS => $row);
			if ($addSearchTermMetaData) {
				$rowData[Piwik_DataTable_Row::METADATA] = array(
					'idSearch' => $row[$addSearchTermMetaData],
					'searchTerm' => $row[self::SEARCH_TERM]
				);
			}
			if ($addUrlMetaData) {
				$rowData[Piwik_DataTable_Row::METADATA]['url'] = $row[self::URL];
			}
			$dataTable->addRow(new Piwik_SiteSearch_ExtendedDataTableRow($rowData));
		}
		
		$id = $dataTable->getId();
		$name = 'SiteSearch_'.$keyword;
		$this->archiveProcessing->insertBlobRecord($name, $dataTable->getSerialized());
		destroy($dataTable);
		
		return $id;
	}
	
}