<?php

/**
 * Piwik - Open source web analytics
 * SiteSearch Plugin
 * Extended DataTable_Row
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @author Timo Besenreuther, EZdesign.de
 *
 * @category Piwik_Plugins
 * @package Piwik_SiteSearch
 */

class Piwik_SiteSearch_ExtendedDataTableRow
		extends Piwik_DataTable_Row {
	
	public function sumRow(Piwik_SiteSearch_ExtendedDataTableRow $rowToSum) {
		foreach ($rowToSum->getColumns() as $columnToSumName => $columnToSumValue) {
			if (in_array($columnToSumName, Piwik_SiteSearch_Archive::$columnsToSum)) {
				// sum column
				$thisColumnValue = $this->getColumn($columnToSumName);
				$newValue = $this->sumRowArray($thisColumnValue, $columnToSumValue);
				$this->setColumn($columnToSumName, $newValue);
			} else if (in_array($columnToSumName, Piwik_SiteSearch_Archive::$columnsToTakeLatest)) {
				// take latest value
				$this->setColumn($columnToSumName, $columnToSumValue);
			}
			// leave other columns untouched
		}
	}
	
}

?>