<?php

/**
 * Site Search Plugin
 * Archive
 *
 * Author:   Timo Besenreuther
 *           EZdesign.de
 * Created:  2010-07-23
 * Modified: 2010-07-25
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
	
	/** State for period archiving */
	private static $periodArchivingState = 0;
	
	private static $instance;
	/** Get singleton instance
	 * @return Piwik_SiteSerach_Archive */
	public static function getInstance() {
		if (self::$instance == null) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	/** Simple file logger */
	public static function log($message) {
		if (is_array($message)) {
			$message = print_r($message, true);
		}
		$log = './tmp/log';
		$fh = fopen($log, 'a') or die('Can\'t open log file');
		fwrite($fh, $message."\n\n");
		fclose($fh);
	}
	
	/** Get data table from archive
     * @return Piwik_DataTable */
    public static function getDataTable($name, $idsite, $period, $date) {
		Piwik::checkUserHasViewAccess($idsite);
		
		$name = 'SiteSearch_'.$name;
		if (!is_string($period)) {
			$periodMap = array(
				'Piwik_Period_Day' => 'day',
				'Piwik_Period_Week' => 'week',
				'Piwik_Period_Month' => 'month',
				'Piwik_Period_Year' => 'year'
			);
			$period = $periodMap[get_class($period)];
		}
		
		$archive = Piwik_Archive::build($idsite, $period, $date);
		$dataTable = $archive->getDataTable($name);
		$dataTable->queueFilter('ReplaceColumnNames',
                array(false, self::$indexToNameMapping));
        $dataTable->applyQueuedFilters();

		return $dataTable;
    }
	
	/** Build archive for a single day */
	public static function archiveDay(Piwik_ArchiveProcessing $archive) {
		$self = self::getInstance();
		$self->extractArchiveProcessing($archive);
		$self->dayAnalyzeKeywords();
		$self->dayAnalyzeAssociatedPages(true);
		$self->dayAnalyzeAssociatedPages(false);
	}
	
	/** Build archive for a period */
	public static function archivePeriod(Piwik_ArchiveProcessing $archive) {
		// make sure, period is only archived once
		if (self::$periodArchivingState == 1) return;
		self::$periodArchivingState = 1;
		
		$self = self::getInstance();
		$self->extractArchiveProcessing($archive);
		
		// archive the main tables
		$archive->archiveDataTable(array(
			'SiteSearch_keywords',
			'SiteSearch_noResults',
			'SiteSearch_followingPages',
			'SiteSearch_previousPages'
		));
		
		$dateStart = $self->period->getDateStart()->getTimestamp();
		$tableName = 'archive_blob_'.date('Y_m');
		
		$sql = '
			SELECT
				name
			FROM
				'.Piwik_Common::prefixTable($tableName).' AS visit
			WHERE
				name LIKE "SiteSearch_followingPages_%" OR
				name LIKE "SiteSearch_previousPages_%"
			GROUP BY
				name
		';
		
		$tables = Piwik_FetchAll($sql);
		foreach ($tables AS $table) {
			$archive->archiveDataTable($table['name']);
		}
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
	
	/**
	 * Analyze keywords
	 * SiteSearch_keywords: archive grouped by keywords
	 * SiteSearch_noResults: archive grouped by keywords with no results
	 */
	private function dayAnalyzeKeywords() {
		$sql = '
			SELECT
				action.search_term AS label,
				action.search_results AS `'.self::INDEX_RESULTS.'`,
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
			WHERE
				visit.idsite = :idsite AND
				action.type = 1 AND
				action.search_term IS NOT NULL AND
				(visit.visit_server_date BETWEEN :startDate AND :endDate)
			GROUP BY
				action.search_term
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
	
	/** Aggregate metadata for keyword rows */
	private function getKeywordsMetadata(&$row) {
		$term = $row['label'];
		return array(
			'search_term' => $term,
			'datatable_following' => $this->dayAnalyzeAssociatedPages(true, $term),
			'datatable_previous' => $this->dayAnalyzeAssociatedPages(false, $term)
		);
	}
	
	/**
	 * Analyze pages associated with the search
	 * following=true, searchTerm=false: all pages that were visited after a search
	 * following=true, searchTerm=x: pages that were after seraching for a certain keyword
	 * following=false, searchTerm=false: pages searches started from
	 */
	private function dayAnalyzeAssociatedPages($following, $searchTerm=false) {
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
		if ($searchTerm) {
			// analyze one search term
			$where = 'AND action_set.search_term = :searchTerm '
			       . 'AND (action_get.search_term IS NULL OR '
			       . 'action_get.search_term != :searchTerm)';
			$bind[':searchTerm'] = $searchTerm;
		} else {
			// analyze all keywords
			$where = 'AND action_set.search_term IS NOT NULL';
		}
		
		$url = $this->site['main_url'];
		if (substr($url, -1) == '/') {
			$url = substr($url, 0, -1);
		}
		$bind[':url'] = $url;
		
		$sql = '
			SELECT
				REPLACE(action_get.name, :url, "") AS label,
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
			WHERE
				visit.idsite = :idsite AND
				visit_action.idaction_url_ref != 0 AND
				(visit.visit_server_date BETWEEN :startDate AND :endDate)
				'.$where.'
			GROUP BY
				action_get.idaction
		';
		
		$data = Piwik_FetchAll($sql, $bind);
		$name = ($following ? 'following' : 'previous').'Pages';
		$isSubTable = $searchTerm ? true : false;
		return $this->archiveDataArray($name, $data, false, $isSubTable);
	}
	
	/**
	 * Build DataTable from array and archive it
	 * If isSubTable is set, the datatable will be stored under SiteSearch_keyword_id
	 * @return id of the datatable
	 */
	private function archiveDataArray($keyword, &$data, $keywordsCallback=false,
			$isSubTable=false) {
		
		$dataTable = new Piwik_DataTable();
		foreach ($data as &$row) {
			$rowData = array(Piwik_DataTable_Row::COLUMNS => $row);
			if ($keywordsCallback) {
				$rowData[Piwik_DataTable_Row::METADATA] =
						$this->getKeywordsMetadata($row);
			}
			$dataTable->addRow(new Piwik_DataTable_Row($rowData));
		}
		
		$id = $dataTable->getId();
		$name = 'SiteSearch_'.$keyword;
		if ($isSubTable) {
			$name .= '_'.$id;
		}
		
		$this->archiveProcessing->insertBlobRecord($name, $dataTable->getSerialized());
		destroy($dataTable);
		
		return $id;
	}
	
}