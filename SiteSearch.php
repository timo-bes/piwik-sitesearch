<?php

/**
 * Piwik - Open source web analytics
 * SiteSearch Plugin
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @author Timo Besenreuther, EZdesign.de
 *
 * @category Piwik_Plugins
 * @package Piwik_SiteSearch
 */

class Piwik_SiteSearch extends Piwik_Plugin {

	/** Information about this plugin */
	public function getInformation() {
		return array(
			'description' => Piwik_Translate('SiteSearch_PluginDescription'),
			'author' => 'Timo Besenreuther, EZdesign',
			'author_homepage' => 'http://www.ezdesign.de/',
			'version' => '0.1.3',
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
		        . 'ADD `search_term` INTEGER NULL DEFAULT NULL';
		$query3 = 'ALTER IGNORE TABLE `'.Piwik_Common::prefixTable('log_action').'` '
		        . 'ADD INDEX `search_term` (`search_term`)';
		$query4 = 'CREATE TABLE `'.Piwik_Common::prefixTable('log_sitesearch').'` ( '
		        . '`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, '
		        . '`idsite` INT NOT NULL, '
		        . '`search_term` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, '
		        . '`results` INT NOT NULL)';
		
		try {
			Zend_Registry::get('db')->query($query1);
		} catch (Exception $e) {
			// if the column already exist do not throw error
		}
		try {
			Zend_Registry::get('db')->query($query2);
		} catch (Exception $e) {}
		try {
			Zend_Registry::get('db')->query($query3);
		} catch (Exception $e) {}
		try {
			Zend_Registry::get('db')->query($query4);
		} catch (Exception $e) {}
	}
	
	/** Uninstall the plugin */
	public function uninstall() {
		$query = 'ALTER TABLE `'.Piwik_Common::prefixTable('site').'` '
		       . 'DROP `sitesearch_url`, '
		       . 'DROP `sitesearch_parameter`';
		
		Zend_Registry::get('db')->query($query);
		
		$query = 'ALTER TABLE `'.Piwik_Common::prefixTable('log_action').'` '
		       . 'DROP `search_term`';
		
		Zend_Registry::get('db')->query($query);
		
		$query = 'DROP TABLE `'.Piwik_Common::prefixTable('log_sitesearch').'`';
		
		Zend_Registry::get('db')->query($query);
	}
	
	/** Register Hooks */
	public function getListHooksRegistered() {
        $hooks = array(
        	'AssetManager.getJsFiles' => 'getJsFiles',
        	'AssetManager.getCssFiles' => 'getCssFiles',
			'Menu.add' => 'addMenu',
			'AdminMenu.add' => 'addAdminMenu',
        	'WidgetsList.add' => 'addWidgets',
        	'Tracker.Action.record' => 'logResults',
            'ArchiveProcessing_Day.compute' => 'archiveDay',
        	'ArchiveProcessing_Period.compute' => 'archivePeriod'
        );
        return $hooks;
    }
    
    /** Add JavaScript */
    public function getJsFiles($notification) {
		$jsFiles = &$notification->getNotificationObject();
		$jsFiles[] = 'plugins/SiteSearch/templates/sitesearch.js';
	}
	
	/** Add CSS */
    public function getCssFiles($notification) {
		$cssFiles = &$notification->getNotificationObject();
		$cssFiles[] = 'plugins/SiteSearch/templates/sitesearch.css';
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
	
	/** Provide Widgets */
	public function addWidgets() {
		Piwik_AddWidget('Site Search',
				Piwik_Translate('SiteSearch_MostPopularInternalSearches'),
				'SiteSearch', 'keywordsWidget');
		Piwik_AddWidget('Site Search',
				Piwik_Translate('SiteSearch_InternalSearchEvolution'),
				'SiteSearch', 'evolutionWidget');
		Piwik_AddWidget('Site Search',
				Piwik_Translate('SiteSearch_PercentageOfSearchUsers'),
				'SiteSearch', 'searchPercentageWidget');
	}

    /** Build archive for a day */
    public function archiveDay($notification) {
		$archiveProcessing = $notification->getNotificationObject();
		Piwik_SiteSearch_Archive::archiveDay($archiveProcessing);
	}
	
	/** Build archive for a period */
	public function archivePeriod($notification) {
		$archiveProcessing = $notification->getNotificationObject();
		Piwik_SiteSearch_Archive::archivePeriod($archiveProcessing);
	}
	
	/** Logger hook: log number of results, if available */
	public function logResults($notification) {
		$action = $notification->getNotificationObject();
		$idaction = $action->getIdActionUrl();
		
		// search results
		$data = Piwik_Common::getRequestVar('data', '');
		$data = html_entity_decode($data);
		$data = json_decode($data, true);
		$resultCount = false;
		if (isset($data['SiteSearch_Results'])) {
			$resultCount = intval($data['SiteSearch_Results']);
		}
		
		// search term
		$sql = '
			SELECT
				site.idsite,
				site.main_url,
				site.sitesearch_url,
				site.sitesearch_parameter
			FROM
				'.Piwik_Common::prefixTable('site').' AS site
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_visit').' AS visit
				ON site.idsite = visit.idsite
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_link_visit_action').' AS link
				ON visit.idvisit = link.idvisit
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_action').' AS action
				ON action.idaction = link.idaction_url
			WHERE
				action.idaction = '.intval($idaction).'
		';
		
		$result = Piwik_FetchAll($sql);
		$site = $result[0];
		
		if (!empty($site['sitesearch_url']) && !empty($site['sitesearch_parameter'])) {
			$url = $site['main_url'];
			if (substr($url, -1) != '/') {
				$url .= '/';
			}
			$url .= $site['sitesearch_url'];
			
			$parameter = $site['sitesearch_parameter'];
			$actionUrl = $action->getActionUrl();
			
			if (substr($actionUrl, 0, strlen($url)) == $url) {
				require_once PIWIK_INCLUDE_PATH .'/plugins/SiteSearch/Archive.php';
				Piwik_SiteSearch_Archive::logAction(array(
					'idaction' => $idaction,
					'name' => $actionUrl
				), $site['idsite'], $site, $resultCount);
			}
		}
	}
	
}