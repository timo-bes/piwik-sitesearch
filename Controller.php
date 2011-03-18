<?php

/**
 * Piwik - Open source web analytics
 * SiteSearch Plugin
 * Controller
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @author Timo Besenreuther, EZdesign.de
 *
 * @category Piwik_Plugins
 * @package Piwik_SiteSearch
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
		$view->keywords = $this->keywords(true);
		$view->noResults = $this->noResults(true);
		$view->searchPercentage = $this->searchPercentage(true);
		$view->period = $this->range->getLocalizedLongString();
		echo $view->render();
	}
	
	/** Search evolution */
	public function evolution($return=false, $footer=false) {
		$idSearch = Piwik_Common::getRequestVar('idSearch', false);
		
		$view = Piwik_SiteSearch_ExtendedChartEvolution::factory('graphEvolution');
		$view->init($this->pluginName, __FUNCTION__, 'SiteSearch.getSearchEvolution');
		if (!is_null($this->date)) {
			$view->setParametersToModify(
					$this->getGraphParamsModified(array('date' => $this->strDate)));
		}
		
		Piwik_SiteSearch_Archive::displayColumns($view, array(
			Piwik_SiteSearch_Archive::UNIQUE_HITS,
			Piwik_SiteSearch_Archive::HITS
		));
		
		if ($idSearch) {
			$view->setRequestParameter('idSearch', $idSearch);
		}
		if (!$footer) {
			$view->disableFooter();
		}
		$result = $this->renderView($view, true);
		
		if ($return) return $result;
		echo $result;
	}
	
	/** Evolution widget */
	public function evolutionWidget() {
		$this->evolution(false, true);
	}
	
	/** Search user percentage */
	public function searchPercentage($return=false, $footer=false) {
		$idSearch = Piwik_Common::getRequestVar('idSearch', false);
		
		$view = Piwik_SiteSearch_ExtendedChartEvolution::factory('graphEvolution');
		$view->init($this->pluginName, __FUNCTION__,
				'SiteSearch.getSearchPercentageEvolution');
		if (!is_null($this->date)) {
			$view->setParametersToModify(
					$this->getGraphParamsModified(array('date' => $this->strDate)));
		}
		
		$view->setColumnTranslation('search_percentage', Piwik_Translate(
				'SiteSearch_SearchUserPercentage'));
    	$view->setColumnsToDisplay('search_percentage');
		
		if (!$footer) {
			$view->disableFooter();
		}
		$result = $this->renderView($view, true);
		
		if ($return) return $result;
		echo $result;
	}
	
	/** Search user percentage widget */
	public function searchPercentageWidget() {
		$this->searchPercentage(false, true);
	}
	
	/** Keywords overview */
	public function keywords($return=false, $limit=20) {
		// manipulate filter column for searchbox
		$_GET['filter_column'] = Piwik_SiteSearch_Archive::SEARCH_TERM;
		
		$view = new Piwik_SiteSearch_ExtendedHtmlTable();
		$view->init($this->pluginName,  __FUNCTION__, 'SiteSearch.getSearchKeywords');
		
		Piwik_SiteSearch_Archive::displayColumns($view, array(
			Piwik_SiteSearch_Archive::SEARCH_TERM,
			Piwik_SiteSearch_Archive::HITS,
			Piwik_SiteSearch_Archive::UNIQUE_HITS,
			Piwik_SiteSearch_Archive::RESULTS
		));
		
		$view->setSortedColumn(Piwik_SiteSearch_Archive::UNIQUE_HITS, 'desc');
		$view->disableFooterIcons();
		$view->setLimit($limit);
		$view->setTemplate('SiteSearch/templates/datatable_keywords.tpl');
		
		$result = $this->renderView($view, true);
		if ($return) return $result;
		echo $result;
	}
	
	/** Keywords widget */
	public function keywordsWidget() {
		$this->keywords(false, 10);
	}
	
	/** Find searches without results */
	public function noResults($return=false) {
		// manipulate filter column for searchbox
		$_GET['filter_column'] = Piwik_SiteSearch_Archive::SEARCH_TERM;
		
		$view = new Piwik_SiteSearch_ExtendedHtmlTable();
		$view->init($this->pluginName,  __FUNCTION__, 'SiteSearch.getNoResults');
		
		Piwik_SiteSearch_Archive::displayColumns($view, array(
			Piwik_SiteSearch_Archive::SEARCH_TERM,
			Piwik_SiteSearch_Archive::HITS,
			Piwik_SiteSearch_Archive::UNIQUE_HITS
		));
		
		$view->setSortedColumn(Piwik_SiteSearch_Archive::UNIQUE_HITS, 'desc');
		$view->disableFooterIcons();
		$view->setTemplate('SiteSearch/templates/datatable_keywords.tpl');
		
		$result = $this->renderView($view, true);
		if ($return) return $result;
		echo $result;
	}
	
	/** Get the pages for a keyword */
	public function pages() {
		$searchTerm = Piwik_Common::getRequestVar('searchTerm', '');
		if ($searchTerm == '') exit;
		
		$idSerach = Piwik_Common::getRequestVar('idSearch', false);
		if (!$idSerach) exit;
		
		$following = Piwik_Common::getRequestVar('following', true) ? true : false;
		echo $this->getPagesTable($searchTerm, $following, $idSerach);
	}
	
	/** Get the pages for a keyword helper */
	private function getPagesTable($searchTerm, $following, $idaction) {
		$view = new Piwik_SiteSearch_ExtendedHtmlTable();
		
		$method = $following ? 'SiteSearch.getFollowingPages' : 'SiteSearch.getPreviousPages';
		$id = __FUNCTION__.($following ? 'Following' : 'Previous');
		$view->init($this->pluginName, $id, $method);
		$view->setRequestParameter('idaction', $idaction);
		
		Piwik_SiteSearch_Archive::displayColumns($view, array(
			Piwik_SiteSearch_Archive::PAGE,
			Piwik_SiteSearch_Archive::HITS
		));
		
		$view->setSortedColumn(Piwik_SiteSearch_Archive::HITS, 'desc');
		$view->disableFooter();
		$view->disableSort();
		$view->setTemplate('SiteSearch/templates/datatable_pages.tpl');
		return $this->renderView($view, true);
	}

    /** Get search refinements */
	public function getRefinements() {
		$searchTerm = Piwik_Common::getRequestVar('searchTerm', false);
		$idSearch = Piwik_Common::getRequestVar('idSearch', false);
		
		$view = new Piwik_SiteSearch_ExtendedHtmlTable();
		$method = 'SiteSearch.getSearchRefinements';
		$view->init($this->pluginName, __FUNCTION__, $method);
		$view->setRequestParameter('idSearch', $idSearch);
		
		$view->setColumnTranslation('searchTerm', Piwik_Translate('SiteSearch_Keyword'));
		$view->setColumnTranslation('unique_hits', Piwik_Translate('SiteSearch_Hits'));
		$view->setColumnTranslation('results', Piwik_Translate('SiteSearch_Results'));
		
		Piwik_SiteSearch_Archive::displayColumns($view, array(
			Piwik_SiteSearch_Archive::SEARCH_TERM,
			Piwik_SiteSearch_Archive::UNIQUE_HITS,
			Piwik_SiteSearch_Archive::RESULTS
		));
		
		$view->setSortedColumn(Piwik_SiteSearch_Archive::UNIQUE_HITS, 'desc');
		$view->disableFooter();
		$view->disableSort();
		$view->setTemplate('SiteSearch/templates/datatable_keywords.tpl');
		echo $this->renderView($view, true);
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
			Piwik_Common::regenerateCacheWebsiteAttributes($idsite);
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
		
		// this is only a rough filter, Piwik_SiteSearch_Archive::logAction
		// will do a more precise check
		$url = '%'.$site['sitesearch_url'].'%'.$site['sitesearch_parameter'].'=%';
		
		$sql = '
			SELECT
				action.idaction,
				action.name
			FROM
				'.Piwik_Common::prefixTable('log_action').' AS action
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_link_visit_action').' AS link
				ON action.idaction = link.idaction_url
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_visit').' AS visit
				ON link.idvisit = visit.idvisit
			WHERE
				action.type = 1 AND
				action.name LIKE :name AND
				visit.idsite = :idSite
			GROUP BY
				action.idaction
		';
		$bind = array(':name' => $url, ':idSite' => intval($idSite));
		$result = Piwik_SiteSearch_Db::fetchAll($sql, $bind);
		
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
		
		$this->clearArchive($idSite);
	}
	
	/** Clear archive for a site */
	private function clearArchive($idSite) {
		$sql = 'SHOW TABLES LIKE "'.Piwik_Common::prefixTable('archive_').'%"';
		$tables = Piwik_FetchAll($sql);
		foreach ($tables as $table) {
			$table = array_values($table);
			$table = $table[0];
			$sql = 'DELETE FROM '.$table.' WHERE idsite = '.intval($idSite);
			Piwik_Query($sql);
		}
	}
	
}
