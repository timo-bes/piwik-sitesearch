<?php

/**
 * Piwik - Open source web analytics
 * SiteSearch Plugin
 * API
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @author Timo Besenreuther, EZdesign.de
 *
 * @category Piwik_Plugins
 * @package Piwik_SiteSearch
 */

class Piwik_SiteSearch_API {
	
	// remember idSearch for filtering associated pages
	private $idSearch;
	
	// singleton instance
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
	
	/** Filter DataTable for search id */
	private function filterDataTable($dataTable, $idSearch) {
		$this->idSearch = $idSearch;
		// other filter can be queued before calling this method
		$dataTable->queueFilter('ColumnCallbackDeleteRow',
				array(Piwik_SiteSearch_Archive::SEARCH_TERM_ID,
				array($this, 'doFilterDataTable')));
		$dataTable->applyQueuedFilters();
	}
	public function doFilterDataTable($idSearch) {
		return $idSearch == $this->idSearch;
	}
	
	/** Returns period object
	 * @return Piwik_Period */
	private function getPeriod($date, $period) {
		return Piwik_Period::factory($period, Piwik_Date::factory($date));
	}
	
	/** Get evolution of search
	 * @return Piwik_DataTable */
	public function getSearchEvolution($idSite, $period, $date) {
		Piwik::checkUserHasViewAccess($idSite);
		$idSearch = Piwik_Common::getRequestVar('idSearch', false);
		
		if (!$idSearch) {
			// render a overview of all keywords
			// data is taken from numeric archive
			$dataTable = Piwik_SiteSearch_Archive::getDataTable(
					array('totalSearches', 'visitsWithSearches'), $idSite, $period,
					$date, true);
		} else {
			// render overview for only one keyword
			// data is taken from general keyword blob archive
			$dataTable = Piwik_SiteSearch_Archive::getDataTable(
					'keywords', $idSite, $period, $date);
			$dataTable->queueFilter('ReplaceColumnNames', array(array(
					'label' => 'label_hidden')));
			$this->filterDataTable($dataTable, $idSearch);
		}
		
		return $dataTable;
	}
	
	/** Get evolution of search percentage
	 * @return Piwik_DataTable */
	public function getSearchPercentageEvolution($idSite, $period, $date) {
		Piwik::checkUserHasViewAccess($idSite);
		
		$archive = Piwik_Archive::build($idSite, $period, $date);
		$dataTable = $archive->getDataTableFromNumeric(
				array('SiteSearch_visitsWithSearches', 'nb_visits'));
		$dataTable->filter('ColumnCallbackAddColumnQuotient',
				array('search_percentage', 'SiteSearch_visitsWithSearches',
				'nb_visits', 4));
		$dataTable->filter('ColumnCallbackReplace',
				array('search_percentage', array($this, 'quotientToPercentageSafe')));
				
		return $dataTable;
	}
	
	public function quotientToPercentageSafe($quotient) {
		return 100 * $quotient;
	}
	
	/** Get search refinements
	 * @return Piwik_DataTable */
	public function getSearchRefinements($idSite, $period, $date) {
		Piwik::checkUserHasViewAccess($idSite);
		$dataTable = Piwik_SiteSearch_Archive::getDataTable(
			'refinements', $idSite, $period, $date);
		
		$idSearch = Piwik_Common::getRequestVar('idSearch', false);
		$this->filterDataTable($dataTable, $idSearch);
				
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
		Piwik::checkUserHasViewAccess($idSite);
		return $this->getAssociatedPages($idSite, true, $period, $date);
	}
	
	/** Get the next sites before keyword was searched
	 * @return Piwik_DataTable */
	public function getPreviousPages($idSite, $period, $date) {
		Piwik::checkUserHasViewAccess($idSite);
		return $this->getAssociatedPages($idSite, false, $period, $date);
	}
	
	/** Get table containing informatino about associated pages
	 * @return Piwik_DataTable */
	private function getAssociatedPages($idSite, $following, $period, $date) {
		Piwik::checkUserHasViewAccess($idSite);
		$idSearch = intval(Piwik_Common::getRequestVar('idSearch', 0));
		$name = ($following ? 'following' : 'previous').'Pages';
		
		$dataTable = Piwik_SiteSearch_Archive
						::getDataTable($name, $idSite, $period, $date);
		
		if (!$idSearch) {
			return $dataTable;
		}
		
		$this->filterDataTable($dataTable, $idSearch);
		return $dataTable;
	}
	
	/** This method is used for accessing the Piwik Mobile report */
	public function getPiwikMobileReport($idSite, $period, $date, $segment=false, $columns=false) {
		Piwik::checkUserHasViewAccess($idSite);
		
		$dataTable = $this->getSearchKeywords($idSite, $period, $date);
		
		$dataTable->queueFilter('ReplaceColumnNames', array(array(
				Piwik_SiteSearch_Archive::UNIQUE_HITS => 'unique_hits',
				'label' => 'label_old',
				Piwik_SiteSearch_Archive::SEARCH_TERM => 'label')));
		
		$dataTable->applyQueuedFilters();
		
		$dataTable->filter('Sort', array('unique_hits', 'desc'));
		
		return $dataTable;
	}
	
}

?>