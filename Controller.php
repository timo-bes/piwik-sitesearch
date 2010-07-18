<?php

/**
 * Site Search Plugin
 * Controller
 *
 * Author:   Timo Besenreuther
 *           EZdesign.de
 * Created:  2010-07-17
 * Modified: 2010-07-18
 */

class Piwik_SiteSearch_Controller extends Piwik_Controller {
	
	/** The plugin index */
	public function index() {
		$view = new Piwik_View('SiteSearch/templates/index.tpl');
		
		// keywords
		$viewDataTable = Piwik_ViewDataTable::factory('table');
		$viewDataTable->init($this->pluginName,  __FUNCTION__, 'SiteSearch.getSearchKeywords');
		$viewDataTable->setColumnTranslation('label', Piwik_Translate('SiteSearch_Keyword'));
		$viewDataTable->setColumnTranslation('hits', Piwik_Translate('SiteSearch_Hits'));
		$viewDataTable->setSortedColumn('hits', 'desc');
		$viewDataTable->setLimit(30);
		$viewDataTable->setColumnsToDisplay(array('label', 'hits'));
		$viewDataTable->disableFooter();
		$viewDataTable->setTemplate('SiteSearch/templates/datatable.tpl');
		$view->keywords = $this->renderView($viewDataTable, true);
		
		// pages
		$view->followingPages = $this->getPagesTable(false, true);
		$view->previousPages = $this->getPagesTable(false, false);
		
		echo $view->render();
	}
	
	/** Get the pages for a keyword */
	public function pages() {
		$idaction = intval(Piwik_Common::getRequestVar('idaction', 0));
		if ($idaction == 0) exit;
		
		$following = Piwik_Common::getRequestVar('following', true) ? true : false;
		echo $this->getPagesTable($idaction, $following);
	}
	
	/** Get the pages for a keyword helper */
	private function getPagesTable($idaction, $following) {
		$idSite = intval(Piwik_Common::getRequestVar('idSite', 0));
		$site = Piwik_SitesManager_API::getInstance()->getSiteFromId($idSite);
		
		$view = new Piwik_View('SiteSearch/templates/pages.tpl');
		
		if ($idaction) {
			$action = Piwik_FetchRow('
				SELECT name
				FROM '.Piwik_Common::prefixTable('log_action').'
				WHERE idaction = '.$idaction.'
			');
			$parameter = $site['sitesearch_parameter'];
			$view->keyword = htmlentities(Piwik_SiteSearch_API::extractKeyword($action['name'], $parameter));
		} else {
			$view->keyword = false;
		}
		
		$viewDataTable = Piwik_ViewDataTable::factory('table');
		$method = $following ? 'SiteSearch.getFollowingPages' : 'SiteSearch.getPreviousPages';
		$id = __FUNCTION__.($following ? 'Following' : 'Previous');
		$viewDataTable->init($this->pluginName, $id, $method, $idaction);
		$viewDataTable->setColumnTranslation('label', Piwik_Translate('SiteSearch_Page'));
		$viewDataTable->setColumnTranslation('hits', Piwik_Translate('SiteSearch_Hits'));
		$viewDataTable->setSortedColumn('hits', 'desc');
		$viewDataTable->setLimit(30);
		$viewDataTable->setColumnsToDisplay(array('label', 'hits'));
		$viewDataTable->disableFooter();
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
		}
	}
	
}

?>