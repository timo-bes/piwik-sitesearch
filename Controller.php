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
		$view = new Piwik_View('SiteSearch/templates/index.tpl');
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