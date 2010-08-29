<?php

/**
 * Site Search Plugin
 * Controller
 *
 * Author:   Timo Besenreuther
 *           EZdesign.de
 * Created:  2010-07-17
 * Modified: 2010-08-29
 */

class Piwik_SiteSearch_Controller extends Piwik_Controller {
	
	public function __construct() {
		parent::__construct();
		$this->period = Piwik_Common::getRequestVar("period");
		if (isset($this->date)) {
			$this->range = Piwik_Period_Range::factory($this->period, $this->date);
		}
	}
	
	/** The plugin index */
	public function index() {
		$view = new Piwik_View('SiteSearch/templates/index.tpl');
		$view->evolution = $this->evolution(true);
		$view->keywords = $this->keywords();
		$view->noResults = $this->noResults();
		echo '<script type="text/javascript" src="plugins/SiteSearch/templates/sitesearch.js"></script>';
		echo $view->render();
	}
	
	/** Search evolution */
	public function evolution($return=false) {
		$searchTerm = Piwik_Common::getRequestVar('search_term', false);
		$idSearch = Piwik_Common::getRequestVar('idSearch', false);
		
		$view = Piwik_SiteSearch_ExtendedChartEvolution::factory('graphEvolution');
		$view->init($this->pluginName, __FUNCTION__, 'SiteSearch.getSearchEvolution');
		if (!is_null($this->date)) {
			$view->setParametersToModify(
					$this->getGraphParamsModified(array('date' => $this->strDate)));
		}
		
		$view->setColumnTranslation('visitsWithSearches', Piwik_Translate('SiteSearch_VisitsWithSearches'));
		$view->setColumnTranslation('totalSearches', Piwik_Translate('SiteSearch_TotalSearches'));
		$view->setColumnsToDisplay(array(
			'totalSearches',
			'visitsWithSearches'
		));
		
		if ($searchTerm) {
			$view->setFooterMessage('Keyword: '.htmlentities($searchTerm));
			$view->setRequestParameter('idSearch', $idSearch);
		}
		
		$result = $this->renderView($view, true);
		
		if ($return) {
			return $result;
		}
		echo $result;
	}
	
	/** Keywords overview */
	private function keywords() {
		$viewDataTable = new Piwik_SiteSearch_ExtendedHtmlTable();
		$viewDataTable->init($this->pluginName,  __FUNCTION__, 'SiteSearch.getSearchKeywords');
		$viewDataTable->setColumnsToDisplay(array('label', 'hits', 'unique_hits', 'results'));
		$viewDataTable->setColumnTranslation('label', Piwik_Translate('SiteSearch_Keyword'));
		$viewDataTable->setColumnTranslation('hits', Piwik_Translate('SiteSearch_Hits'));
		$viewDataTable->setColumnTranslation('unique_hits', Piwik_Translate('SiteSearch_UniqueHits'));
		$viewDataTable->setColumnTranslation('results', Piwik_Translate('SiteSearch_Results'));
		$viewDataTable->setSortedColumn('unique_hits', 'desc');
		$viewDataTable->disableFooterIcons();
		$viewDataTable->setLimit(20);
		$viewDataTable->setTemplate('SiteSearch/templates/datatable.tpl');
		return $this->renderView($viewDataTable, true);
	}
	
	/** Find searches without results */
	public function noResults() {
		$view = Piwik_ViewDataTable::factory('table');
		$view->init($this->pluginName,  __FUNCTION__, 'SiteSearch.getNoResults');
		$view->setColumnsToDisplay(array('label', 'hits', 'unique_hits'));
		$view->setColumnTranslation('label', Piwik_Translate('SiteSearch_Keyword'));
		$view->setColumnTranslation('hits', Piwik_Translate('SiteSearch_Hits'));
		$view->setColumnTranslation('unique_hits', Piwik_Translate('SiteSearch_UniqueHits'));
		$view->setSortedColumn('label', 'asc');
		$view->disableFooterIcons();
		return $this->renderView($view, true);
	}
	
	/** Get the pages for a keyword */
	public function pages() {
		$searchTerm = Piwik_Common::getRequestVar('search_term', '');
		if ($searchTerm == '') exit;
		
		$idSerach = Piwik_Common::getRequestVar('idSearch', false);
		if (!$idSerach) exit;
		
		$following = Piwik_Common::getRequestVar('following', true) ? true : false;
		echo $this->getPagesTable($searchTerm, $following, $idSerach);
	}
	
	/** Get the pages for a keyword helper */
	private function getPagesTable($searchTerm, $following, $idaction) {
		$view = new Piwik_View('SiteSearch/templates/pages.tpl');
		$view->keyword = $searchTerm;
		$view->period = $this->range->getLocalizedLongString();
		
		$viewDataTable = new Piwik_SiteSearch_ExtendedHtmlTable();
		$method = $following ? 'SiteSearch.getFollowingPages' : 'SiteSearch.getPreviousPages';
		$id = __FUNCTION__.($following ? 'Following' : 'Previous');
		$viewDataTable->init($this->pluginName, $id, $method);
		$viewDataTable->setRequestParameter('idaction', $idaction);
		$viewDataTable->setColumnTranslation('page', Piwik_Translate('SiteSearch_Page'));
		$viewDataTable->setColumnTranslation('hits', Piwik_Translate('SiteSearch_Hits'));
		$viewDataTable->setSortedColumn('hits', 'desc');
		$viewDataTable->setColumnsToDisplay(array('page', 'hits'));
		$viewDataTable->disableFooterIcons();
		$view->table = $this->renderView($viewDataTable, true);
		
		return $view->render();
	}

    /** Get search refinements */
	public function getRefinements() {
        $searchTerm = Piwik_Common::getRequestVar('search_term', false);
        
		$viewDataTable = new Piwik_SiteSearch_ExtendedHtmlTable();
		$method = 'SiteSearch.getSearchRefinements';
		$viewDataTable->init($this->pluginName, __FUNCTION__, $method);
		$viewDataTable->setRequestParameter('search_term', $searchTerm);
		$viewDataTable->setColumnTranslation('keyword', Piwik_Translate('SiteSearch_Keyword'));
		$viewDataTable->setColumnTranslation('hits', Piwik_Translate('SiteSearch_Hits'));
		$viewDataTable->setSortedColumn('hits', 'desc');
		$viewDataTable->setColumnsToDisplay(array('keyword', 'hits'));
		$viewDataTable->disableFooterIcons();

		echo $this->renderView($viewDataTable, true);
	}

	/** Administration index */
	public function admin() {
		Piwik::checkUserIsSuperUser();
		
		$data = Piwik_Common::getRequestVar('SiteSearch_Data', array());
		if (is_array($data) && count($data)) {
			$this->setParameters($data);
		}
		
		$view = new Piwik_View('SiteSearch/templates/admin.tpl');
		$view->menu = Piwik_GetAdminMenu();
		$this->setGeneralVariablesView($view);
		
		$sitesList = Piwik_SitesManager_API::getInstance()->getSitesWithAdminAccess();
		$view->assign('sitesList', $sitesList);
		
		echo $view->render();
	}
	
	/** Save search url and parameter names */
	private function setParameters($data) {
		$db = Zend_Registry::get('db');
		foreach($data as $idsite => $siteData) {
			$db->update(Piwik_Common::prefixTable('site'),
					array('sitesearch_parameter' => $siteData['parameter'],
					'sitesearch_url' => $siteData['url']),
					'idsite = '.intval($idsite));
			if (isset($siteData['analyze']) && $siteData['analyze'] == 1) {
				$this->analyzeSite($idsite);
			}
		}
	}
	
	/** Analyze site for serach URLs */
	private function analyzeSite($idSite) {
		// remove all searchterms from actions
		Piwik_Query('
			UPDATE '.Piwik_Common::prefixTable('log_action').' AS action
			SET search_term = NULL
			WHERE search_term IS NOT NULL AND EXISTS (
				SELECT
					search.idsite
				FROM
					'.Piwik_Common::prefixTable('log_sitesearch').' AS search
				WHERE
					search.idsite = '.intval($idSite).' AND
					search.id = action.search_term
			)
		');
		
		// rescan
		$site = Piwik_SitesManager_API::getInstance()->getSiteFromId($idSite);
		if (empty($site['sitesearch_url']) || empty($site['sitesearch_parameter'])) {
			return;
		}
		
		$url = $site['main_url'];
		if (substr($url, -1) != '/') {
			$url .= '/';
		}
		$url .= $site['sitesearch_url'];
		
		$sql = '
			SELECT idaction, name
			FROM '.Piwik_Common::prefixTable('log_action').'
			WHERE type = 1 AND name LIKE :name
		';
		$bind = array(':name' => $url.'%');
		$result = Piwik_FetchAll($sql, $bind);
		
		foreach ($result as $action) {
			Piwik_SiteSearch_Archive::logAction($action, $idSite, $site);
		}
		
		// remove unneccessary sitesearch entries
		$sql = '
			DELETE FROM '.Piwik_Common::prefixTable('log_sitesearch').'
			WHERE idsite = '.intval($idSite).' AND NOT EXISTS (
				SELECT action.idaction
				FROM '.Piwik_Common::prefixTable('log_action').' AS action
				WHERE action.search_term = id
			)
		';
		Piwik_Query($sql);
	}
	
}