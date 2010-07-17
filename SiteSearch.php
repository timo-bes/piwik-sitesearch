<?php

/**
 * Site Search Plugin
 * 
 * Author:   Timo Besenreuther
 *           EZdesign.de
 * Created:  2010-07-17
 * Modified: 2010-07-17
 */

class Piwik_SiteSearch extends Piwik_Plugin {
	
	/** Information about this plugin */
	public function getInformation() {
		return array(
			'description' => Piwik_Translate('SiteSearch_PluginDescription'),
			'author' => 'Timo Besenreuther, EZdesign',
			'author_homepage' => 'http://www.ezdesign.de/',
			'version' => '0.1',
			'translationAvailable' => true,
		);
	}
	
	/** Register Hooks */
	public function getListHooksRegistered(){
        $hooks = array(
			'Menu.add' => 'addMenu'
        );
        return $hooks;
    }
	
	/** Menu hook */
	public function addMenu() {
		Piwik_AddMenu('Actions_Actions', 'SiteSearch_SubmenuSiteSearch',
				array('module' => 'SiteSearch', 'action' => 'index'));
	}
}

?>