<?php

/**
 * Site Search Plugin
 * Controller
 *
 * Author:   Timo Besenreuther
 *           EZdesign.de
 * Created:  2010-07-17
 * Modified: 2010-07-17
 */

class Piwik_SiteSearch_Controller extends Piwik_Controller {
	
	/** The plugin index */
	public function index() {
		$viewDataTable = Piwik_ViewDataTable::factory('table');
		$viewDataTable->init($this->pluginName,  __FUNCTION__, 'SiteSearch.getSearchKeywords');
		$viewDataTable->setColumnTranslation('label', Piwik_Translate('SiteSearch_Keyword'));
		$viewDataTable->setColumnTranslation('hits', Piwik_Translate('SiteSearch_Hits'));
		$viewDataTable->setSortedColumn('hits', 'desc');
		$viewDataTable->setLimit(30);
		$viewDataTable->setColumnsToDisplay(array('label', 'hits'));
		$viewDataTable->setTemplate('SiteSearch/templates/datatable.tpl');
		
		$view = new Piwik_View('SiteSearch/templates/index.tpl');
		$view->keywords = $this->renderView($viewDataTable, true);
		
		echo $view->render();
	}
	
	/** Get the pages for a keywprd */
	public function pages() {
		$idaction = intval(Piwik_Common::getRequestVar('idaction', 0));
		if ($idaction == 0) exit;
		
		$idSite = intval(Piwik_Common::getRequestVar('idSite', 0));
		$site = Piwik_SitesManager_API::getInstance()->getSiteFromId($idSite);
		
		$view = new Piwik_View('SiteSearch/templates/pages.tpl');
		
		$action = Piwik_FetchRow('
			SELECT name
			FROM '.Piwik_Common::prefixTable('log_action').'
			WHERE idaction = '.$idaction.'
		');
		
		$parameter = $site['sitesearch_parameter'];
		$view->keyword = htmlentities(Piwik_SiteSearch_API::extractKeyword($action['name'], $parameter));
		
		$viewDataTable = Piwik_ViewDataTable::factory('table');
		$viewDataTable->init($this->pluginName,  __FUNCTION__, 'SiteSearch.getKeywordPages', $idaction);
		$viewDataTable->setColumnTranslation('label', Piwik_Translate('SiteSearch_Page'));
		$viewDataTable->setColumnTranslation('hits', Piwik_Translate('SiteSearch_Hits'));
		$viewDataTable->setSortedColumn('hits', 'desc');
		$viewDataTable->setLimit(30);
		$viewDataTable->setColumnsToDisplay(array('label', 'hits'));
		$view->table = $this->renderView($viewDataTable, true);
		
		echo $view->render();
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