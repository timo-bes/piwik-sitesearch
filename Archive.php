<?php

/**
 * Site Search Plugin
 * Archive
 *
 * Author:   Timo Besenreuther
 *           EZdesign.de
 * Created:  2010-07-23
 * Modified: 2010-07-24
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
    public static function getDataTable($name, $idsite, $period, $date) {
		Piwik::checkUserHasViewAccess($idsite);
		
		$name = 'SiteSearch_'.$name;
		$periodMap = array(
			'Piwik_Period_Day' => 'day',
			'Piwik_Period_Week' => 'week',
			'Piwik_Period_Month' => 'month',
			'Piwik_Period_Year' => 'year'
		);
		$period = $periodMap[get_class($period)];
		
        $archive = Piwik_Archive::build($idsite, $period, $date);
        $dataTable = $archive->getDataTable($name);
        
        // TODO: sort somewhere else
		$dataTable->queueFilter('Sort', array('unique_hits', 'desc', false));
		$dataTable->queueFilter('ReplaceColumnNames',
                array(false, self::$indexToNameMapping));
        $dataTable->applyQueuedFilters();

		return $dataTable;
    }
	
	/** Build archive for a single day */
	public static function archiveDay(Piwik_ArchiveProcessing $archive) {
		$self = self::getInstance();
		$self->archiveProcessing = $archive;
		$self->idsite = intval($archive->idsite);
		$self->startDate = $archive->getStartDatetimeUTC();
		$self->endDate = $archive->getEndDatetimeUTC();
		$self->dayAnalyzeKeywords();
	}
	
	/** Build archive for a period */
	public static function archivePeriod(Piwik_ArchiveProcessing $archive) {
		$archive->archiveDataTable(array(
			'SiteSearch_keywords',
			'SiteSearch_noResults',
		));
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
				visit.idsite = :idsite AND
				action.type = 1 AND
				action.search_term IS NOT NULL AND
				(visit.visit_server_date BETWEEN :startDate AND :endDate)
			GROUP BY
				action.search_term
		';

		$result = Piwik_FetchAll($sql, $this->getSqlBindings());

		$keywordsData = array();
		$noResultsData = array();
		foreach ($result as &$search) {
			// aggregate keyword data
			$label = $search['label'];
			if (!isset($keywordsData[$label])) {
				$keywordsData[$label] = $this->getNewArchiveRow($label);
			}
			$this->updateArchiveRow($search, $keywordsData[$label]);
			// aggreate no results data
			if (!$search['results']) {
				if (!isset($noResultsData[$label])) {
					$noResultsData[$label] = $this->getNewArchiveRow($label);
				}
				$this->updateArchiveRow($search, $noResultsData[$label]);
			}
		}
		
		$this->archiveDataArray('keywords', $keywordsData);
		$this->archiveDataArray('noResults', $noResultsData);
	}
	
	/** Build DataTable from array and archive it */
	private function archiveDataArray($keyword, &$data) {
		$dataTable = new Piwik_DataTable();
		foreach ($data as &$row) {
			$rowData = array(Piwik_DataTable_Row::COLUMNS => $row);
			if (isset($row['label'])) {
				$rowData[Piwik_DataTable_Row::METADATA] = array(
					'search_term' => $row['label']
				);
			}
			$dataTable->addRow(new Piwik_DataTable_Row($rowData));
		}
		
		$this->archiveProcessing->insertBlobRecord('SiteSearch_'.$keyword,
				$dataTable->getSerialized());
		
		destroy($dataTable);
	}
	
	/** Returns an empty row containing default values for archiving */
	public function getNewArchiveRow($label) {
		return array(
			'label' => $label,
			self::INDEX_SUM_HITS => 0,
			self::INDEX_SUM_UNIQUE_HITS => 0,
			self::INDEX_RESULTS => 0
		);
	}

	/** Adds a new record to the existing archive row */
	public function updateArchiveRow($add, &$row) {
		$row[self::INDEX_SUM_HITS] += $add['hits'];
		$row[self::INDEX_SUM_UNIQUE_HITS] += $add['unique_hits'];
		$row[self::INDEX_RESULTS] = $add['results'];
	}
	
}