<?php

/**
 * Extended DataTable_Row
 *
 * Author:   Timo Besenreuther
 *           EZdesign.de
 * Created:  2010-08-30
 * Modified: 2010-08-31
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