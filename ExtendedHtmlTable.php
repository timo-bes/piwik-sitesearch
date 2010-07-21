<?php

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