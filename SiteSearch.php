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

    /** Archive indexes */
    const INDEX_SEARCH_TERM = 0;
    const INDEX_SUM_HITS = 1;
    const INDEX_SUM_UNIQUE_HITS = 2;
    const INDEX_RESULTS = 3;
    

    public static $indexToNameMapping = array(
		self::INDEX_SUM_HITS => 'hits',
        self::INDEX_SUM_UNIQUE_HITS => 'unique_hits',
        self::INDEX_RESULTS => 'results',
        self::INDEX_SEARCH_TERM => 'label'
    );

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
	
	/** Register Hooks */
	public function getListHooksRegistered() {
        $hooks = array(
			'Menu.add' => 'addMenu',
			'AdminMenu.add' => 'addAdminMenu',
        	'Tracker.Action.record' => 'logResults',
            'ArchiveProcessing_Day.compute' => 'archiveDay'
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
		$this->archiveDayAggregateKeywords($archiveProcessing);
	}

    /** Archive day: keywords */
	protected function archiveDayAggregateKeywords($archiveProcessing) {
        $idsite = $archiveProcessing->idsite;
        $from = $archiveProcessing->getStartDatetimeUTC();
        $to = $archiveProcessing->getEndDatetimeUTC();

        $sql = '
			SELECT
				action.idaction,
				action.search_term AS label,
				action.search_results AS results,
				COUNT(action.idaction) AS hits,
				COUNT(DISTINCT visit.idvisit) AS unique_hits
			FROM
				'.Piwik_Common::prefixTable('log_visit').' AS visit
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_link_visit_action').' AS visit_action
				ON visit.idvisit = visit_action.idvisit
			LEFT JOIN
				'.Piwik_Common::prefixTable('log_action').' AS action
				ON action.idaction = visit_action.idaction_url
			WHERE
				visit.idsite = '.intval($idsite).' AND
				action.type = 1 AND
				action.search_term IS NOT NULL AND
				(visit.visit_server_date BETWEEN "'.$from.'" AND "'.$to.'")
			GROUP BY
				action.search_term
		';

		$result = Piwik_FetchAll($sql);

        $archiveData = array();
		foreach ($result as $search) {
            $label = $search['label'];
			if (!isset($interest[$label])) {
                $archiveData[$label] = $this->getNewArchiveRow($label);
            }
			$this->updateArchiveRow($search, $archiveData[$label]);
		}

        $dataTable = new Piwik_DataTable();
        foreach ($archiveData as &$searchView) {
			$dataTable->addRow(new Piwik_DataTable_Row(array(
				Piwik_DataTable_Row::COLUMNS => $searchView,
				Piwik_DataTable_Row::METADATA => array(
					'search_term' => $searchView[self::INDEX_SEARCH_TERM]
				)
			)));
		}

		$archiveProcessing->insertBlobRecord('SiteSearch_keywords',
                $dataTable->getSerialized());

		destroy($dataTable);
	}

    /** Returns an empty row containing default values for archiving */
	public function getNewArchiveRow($searchTerm) {
		return array(
            self::INDEX_SEARCH_TERM => $searchTerm,
            self::INDEX_SUM_HITS => 0,
            self::INDEX_SUM_UNIQUE_HITS => 0,
            self::INDEX_RESULTS => 0
		);
	}

    /** Adds a new record to the existing archive row */
	public function updateArchiveRow($add, &$row) {
		$row[self::INDEX_SUM_HITS] += $add['hits'];
        $row[self::INDEX_SUM_UNIQUE_HITS] += $add['unique_hits'];
        $row[self::INDEX_RESULTS] = $add['results'];
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