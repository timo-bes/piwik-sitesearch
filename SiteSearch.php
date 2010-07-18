<?php

/**
 * Site Search Plugin
 *
 * Author:   Timo Besenreuther
 *           EZdesign.de
 * Created:  2010-07-17
 * Modified: 2010-07-18
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
			'TrackerPlugin' => true
		);
	}
	
	/** Install the plugin */
	public function install() {
		$query1 = 'ALTER IGNORE TABLE `'.Piwik_Common::prefixTable('site').'` '
		        . 'ADD `sitesearch_url` VARCHAR( 100 ) NULL, '
		        . 'ADD `sitesearch_parameter` VARCHAR( 100 ) NULL';
		$query2 = 'ALTER IGNORE TABLE `'.Piwik_Common::prefixTable('log_action').'` '
		        . 'ADD `search_results` INTEGER NULL';
		try {
			Zend_Registry::get('db')->query($query1);
		} catch (Exception $e) {
			// if the column already exist do not throw error
		}
		try {
			Zend_Registry::get('db')->query($query2);
		} catch (Exception $e) {}
	}
	
	/** Uninstall the plugin */
	public function uninstall() {
		$query = 'ALTER TABLE `'.Piwik_Common::prefixTable('site').'` '
		       . 'DROP `sitesearch_parameter`';
		
		Zend_Registry::get('db')->query($query);
	}
	
	/** Register Hooks */
	public function getListHooksRegistered() {
        $hooks = array(
			'Menu.add' => 'addMenu',
			'AdminMenu.add' => 'addAdminMenu',
        	'Tracker.Action.record' => 'logResults'
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
	
	/** Logger hook: log number of results, if available */
	public function logResults($notification) {
		$action = $notification->getNotificationObject();
		$idaction = $action->getIdActionUrl();
		
		$data = Piwik_Common::getRequestVar('data', '');
		$data = html_entity_decode($data);
		$data = json_decode($data, true);
		if (!isset($data['SiteSearch_Results'])) return;
		$resultCount = intval($data['SiteSearch_Results']);
		
		Piwik_Query('
			UPDATE `'.Piwik_Common::prefixTable('log_action').'`
			SET search_results = '.intval($resultCount).'
			WHERE idaction = '.intval($idaction).'
		');
	}
	
}

?>