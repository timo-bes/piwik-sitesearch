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
		
		$parameters = Piwik_Common::getRequestVar('SiteSearch_Parameters', array());
		if (is_array($parameters) && count($parameters)) {
			$this->setParameters($parameters);
		}
		
		$view = new Piwik_View('SiteSearch/templates/admin.tpl');
		$view->menu = Piwik_GetAdminMenu();
		$this->setGeneralVariablesView($view);
		
		$sitesList = Piwik_SitesManager_API::getInstance()->getSitesWithAdminAccess();
		$view->assign('sitesList', $sitesList);
		
		echo $view->render();
	}
	
	/** Save search parameter names */
	private function setParameters($parameters) {
		$db = Zend_Registry::get('db');
		foreach($parameters as $idsite => $param) {
			$db->update(Piwik_Common::prefixTable('site'), 
					array('sitesearch_parameter' => $param),
					'idsite = '.intval($idsite));
		}
	}
	
}

?>