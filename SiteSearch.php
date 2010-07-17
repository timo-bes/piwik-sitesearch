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
	
	/** Install the plugin */
	function install() {
		echo $query = 'ALTER IGNORE TABLE `'.Piwik_Common::prefixTable('site').'` '
		       . 'ADD `sitesearch_parameter` VARCHAR( 100 ) NULL';
		try {
			Zend_Registry::get('db')->query($query);
		} catch (Exception $e) {
			// if the column already exist do not throw error
		}
	}
	
	/** Uninstall the plugin */
	public function uninstall() {
		$query = 'ALTER TABLE `'.Piwik_Common::prefixTable('site').'` '
		       . 'DROP `sitesearch_parameter`';
		
		Zend_Registry::get('db')->query($query);
	}
	
	/** Register Hooks */
	public function getListHooksRegistered(){
        $hooks = array(
			'Menu.add' => 'addMenu',
			'AdminMenu.add' => 'addAdminMenu'
        );
        return $hooks;
    }
	
	/** Normal menu hook */
	public function addMenu() {
		Piwik_AddMenu('Actions_Actions', 'SiteSearch_SiteSearch',
				array('module' => 'SiteSearch', 'action' => 'index'));
	}
	
	/** Admin menu hook */
	public function addAdminMenu() {
		Piwik_AddAdminMenu('SiteSearch_SiteSearch',
			array('module' => 'SiteSearch', 'action' => 'admin'),
			Piwik::isUserIsSuperUser(), 8);
	}
}

?>