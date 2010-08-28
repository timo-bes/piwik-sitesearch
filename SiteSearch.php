<?php

/**
 * Site Search Plugin
 *
 * Author:   Timo Besenreuther
 *           EZdesign.de
 * Created:  2010-07-17
 * Modified: 2010-07-22
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
		        . 'ADD `search_term` VARCHAR( 255 ) NULL,'
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
		       . 'DROP `sitesearch_url`, '
		       . 'DROP `sitesearch_parameter`';
		
		Zend_Registry::get('db')->query($query);
		
		$query = 'ALTER TABLE `'.Piwik_Common::prefixTable('log_action').'` '
		       . 'DROP `search_term`, '
		       . 'DROP `search_results`';
		
		Zend_Registry::get('db')->query($query);
	}
	
	/** Simple file logger */
	public static function log($message) {
		if (is_array($message)) {
			$message = print_r($message, true);
		}
		$log = './plugins/SiteSearch/dev/log';
		$fh = fopen($log, 'a') or die('Can\'t open log file');
		fwrite($fh, $message."\n\n");
		fclose($fh);
	}
	
	/** Register Hooks */
	public function getListHooksRegistered() {
        $hooks = array(
			'Menu.add' => 'addMenu',
			'AdminMenu.add' => 'addAdminMenu',
        	'Tracker.Action.record' => 'logResults',
            'ArchiveProcessing_Day.compute' => 'archiveDay',
        	'ArchiveProcessing_Period.compute' => 'archivePeriod'
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
		
		// search term
		$sql = '
			SELECT
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
				$hit = preg_match('/'.$parameter.'=(.*?)(&|$)/i', $actionUrl, $match);
				if ($hit) {
					$sql = '
						UPDATE '.Piwik_Common::prefixTable('log_action').'
						SET search_term = :searchTerm
						WHERE idaction = '.intval($idaction).'
					';
					Piwik_Query($sql, array(':searchTerm' => urldecode($match[1])));
				}
			}
		}
		
		// search results
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