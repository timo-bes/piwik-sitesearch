<?php

/**
 * Piwik - Open source web analytics
 * SiteSearch Plugin
 * Extended HTML Data Table
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @author Timo Besenreuther, EZdesign.de
 *
 * @category Piwik_Plugins
 * @package Piwik_SiteSearch
 */

class Piwik_SiteSearch_ExtendedHtmlTable
		extends Piwik_ViewDataTable_HtmlTable {
	
	protected $extraRequestParams = array();
			
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