<?php

/**
 * Site Search Plugin
 * Controller
 *
 * Author:   Timo Besenreuther
 *           EZdesign.de
 * Created:  2010-07-17
 * Modified: 2010-07-22
 */

class Piwik_SiteSearch_Controller extends Piwik_Controller {
	
	public function __construct() {
		parent::__construct();
		$this->period = Piwik_Common::getRequestVar("period");
		$this->range = Piwik_Period_Range::factory($this->period, $this->date);
	}
	
	/** The plugin index */
	public function index() {
		$view = new Piwik_View('SiteSearch/templates/index.tpl');
		$view->evolution = $this->evolution(true);
		$view->keywords = $this->keywords(true);
		$view->noResults = $this->noResults();
		$view->followingPages = $this->getPagesTable(false, true);
		$view->previousPages = $this->getPagesTable(false, false);
		echo '<script type="text/javascript" src="plugins/SiteSearch/templates/sitesearch.js"></script>';
		echo $view->render();
	}
	
	/** Search evolution */
	public function evolution($return=false) {
		$searchTerm = Piwik_Common::getRequestVar('search_term', false);
		
		$graph = Piwik_SiteSearch_ExtendedChartEvolution::factory('graphEvolution');
		$graph->init($this->pluginName,  __FUNCTION__, 'SiteSearch.getSearchEvolution');
		$graph->setColumnTranslation('visitsWithSearches', 'Visits with Searches');
		$graph->setColumnTranslation('totalSearches', 'Total Searches');
		
		if ($searchTerm) {
			$graph->setFooterMessage('Keyword: '.htmlentities($searchTerm));
			$graph->setRequestParameter('search_term', $searchTerm);
		}
		
		$result = $this->renderView($graph, true);
		
		if ($return) {
			return $result;
		}
		echo $result;
	}
	
	/** Keywords overview */
	public function keywords($return=false) {
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
		$result = $this->renderView($viewDataTable, true);
		if ($return) {
			return $result;
		}
		echo $result;
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
		
		$following = Piwik_Common::getRequestVar('following', true) ? true : false;
		echo $this->getPagesTable($searchTerm, $following);
	}
	
	/** Get the pages for a keyword helper */
	private function getPagesTable($searchTerm, $following) {
		$view = new Piwik_View('SiteSearch/templates/pages.tpl');
		$view->keyword = $searchTerm;
		$view->period = $this->range->getLocalizedLongString();
		
		$viewDataTable = new Piwik_SiteSearch_ExtendedHtmlTable();
		$method = $following ? 'SiteSearch.getFollowingPages' : 'SiteSearch.getPreviousPages';
		$id = __FUNCTION__.($following ? 'Following' : 'Previous');
		$viewDataTable->init($this->pluginName, $id, $method);
		$viewDataTable->setRequestParameter('search_term', $searchTerm);
		$viewDataTable->setColumnTranslation('label', Piwik_Translate('SiteSearch_Page'));
		$viewDataTable->setColumnTranslation('hits', Piwik_Translate('SiteSearch_Hits'));
		$viewDataTable->setSortedColumn('hits', 'desc');
		$viewDataTable->setColumnsToDisplay(array('label', 'hits'));
		$viewDataTable->disableFooterIcons();
		$view->table = $this->renderView($viewDataTable, true);
		
		return $view->render();
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
		// remove all searchterms from db
		Piwik_Query('
			UPDATE '.Piwik_Common::prefixTable('log_action').' AS action
			SET search_term = NULL
			WHERE type = 1 AND search_term IS NOT NULL AND EXISTS (
				SELECT
					visit.idsite
				FROM
					'.Piwik_Common::prefixTable('log_link_visit_action').' AS link
				LEFT JOIN
					'.Piwik_Common::prefixTable('log_visit').' AS visit
					ON visit.idvisit = link.idvisit
				WHERE
					visit.idsite = '.intval($idSite).' AND
					link.idaction_url = action.idaction
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
			WHERE type = 1 AND name LIKE "'.mysql_escape_string($url).'%"
		';
		$result = Piwik_FetchAll($sql);
		$parameter = $site['sitesearch_parameter'];
		foreach ($result as $action) {
			$hit = preg_match('/'.$parameter.'=(.*?)(&|$)/i', $action['name'], $match);
			if ($hit) {
				Piwik_Query('
					UPDATE '.Piwik_Common::prefixTable('log_action').'
					SET search_term = "'.mysql_escape_string(urldecode($match[1])).'"
					where idaction = '.intval($action['idaction']).'
				');
			}
		}
	}
	
}

?>