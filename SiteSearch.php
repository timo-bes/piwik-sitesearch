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
        	'ArchiveProcessing_Period.compute' => 'archivePeriod',
        	'Common.fetchWebsiteAttributes' => 'recordWebsiteDataInCache',
        	'API.getReportMetadata' => 'getReportMetadata'
        );
        return $hooks;
    }
    
	/** The report metadata allows Piwik Mobile to discover the plugin */
    public function getReportMetadata($notification) {
		$reports = &$notification->getNotificationObject();
		
		$metrics = array('unique_hits' => Piwik_Translate('SiteSearch_UniqueHits'));
		
		$reports[] = array(
			'category' => Piwik_Translate('Actions_Actions'),
			'name' => Piwik_Translate('SiteSearch_SiteSearch'),
			'module' => 'SiteSearch',
			'action' => 'getPiwikMobileReport',
			'dimension' => Piwik_Translate('SiteSearch_Keyword'),
			'metrics' => $metrics,
			'processedMetrics' => false,
			'order' => 50
		);
	}
    
    /** Add SiteSearch config to tracker cache */
    public function recordWebsiteDataInCache($notification) {
    	$idsite = $notification->getNotificationInfo();
		$cache =& $notification->getNotificationObject();
		
		$sql = '
			SELECT sitesearch_url, sitesearch_parameter
			FROM '.Piwik_Common::prefixTable('site').' AS site
			WHERE idsite = '.intval($idsite).'
		';
		
		$result = Piwik_FetchAll($sql);
		$site = $result[0];
		
		$cache['sitesearch_url'] = $site['sitesearch_url'];
		$cache['sitesearch_parameter'] = $site['sitesearch_parameter'];
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
		
		// load site config from tracker cache
		$info = $notification->getNotificationInfo();
		$idsite = $info['idSite'];
		$site = Piwik_Common::getCacheWebsiteAttributes($idsite);
		$site['idsite'] = $idsite;
		
		// search results passed via JS tracker
		$data = Piwik_Common::getRequestVar('data', '');
		$data = Piwik_Common::unsanitizeInputValue($data);
		$data = json_decode($data, true);
		$resultCount = false;
		if (isset($data['SiteSearch_Results'])) {
			$resultCount = intval($data['SiteSearch_Results']);
		}
		
		if (!empty($site['sitesearch_url']) && !empty($site['sitesearch_parameter'])) {
			
			// check whether action is a site search
			$url = preg_quote($site['sitesearch_url'], '/');
			$param = preg_quote($site['sitesearch_parameter'], '/');
			$regex = '/'.$url.'(.*)(&|\?)'.$param.'=(.*?)(&|$)/i';
			
			$actionUrl = $action->getActionUrl();
			
			if (preg_match($regex, $actionUrl, $matches)) {
				require_once PIWIK_INCLUDE_PATH .'/plugins/SiteSearch/Archive.php';
				require_once PIWIK_INCLUDE_PATH .'/plugins/SiteSearch/Db.php';
				Piwik_SiteSearch_Archive::logAction(array(
					'idaction' => $idaction,
					'name' => $actionUrl
				), $site['idsite'], $site, $resultCount);
			}
		}
	}
	
}