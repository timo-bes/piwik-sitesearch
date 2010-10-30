<?php

/**
 * Piwik - Open source web analytics
 * SiteSearch Plugin
 * Extended Evolution Chart
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @author Timo Besenreuther, EZdesign.de
 *
 * @category Piwik_Plugins
 * @package Piwik_SiteSearch
 */

class Piwik_SiteSearch_ExtendedChartEvolution
		extends Piwik_ViewDataTable_GenerateGraphHTML_ChartEvolution {
			
	protected $extraRequestParams = array();

	static public function factory($defaultType=null, $force=false) {
		if (is_null($defaultType)) {
			$defaultType = 'table';
		}
		
		if ($force === true) {
			$type = $defaultType;
		} else {
			$type = Piwik_Common::getRequestVar('viewDataTable', $defaultType, 'string');
		}
		
		switch ($type) {
			
		case 'graphEvolution':
			return new Piwik_SiteSearch_ExtendedChartEvolution();
		
		case 'generateDataChartEvolution':
			return new Piwik_ViewDataTable_GenerateGraphData_ChartEvolution();
		
		case 'table':
		default:
			return new Piwik_ViewDataTable_HtmlTable();
		
		}
	}
	
	public function setRequestParameter($param, $value) {
		$this->extraRequestParams[$param] = $value;
	}
			
	protected function getRequestString() {
		$string = parent::getRequestString();
		
		foreach ($this->extraRequestParams as $param => $value) {
			$string .= '&'.$param.'='.urlencode($value);
		}
		
		return $string;
	}
	
}

?>