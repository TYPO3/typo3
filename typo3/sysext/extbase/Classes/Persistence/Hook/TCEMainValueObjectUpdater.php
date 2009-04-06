<?php
/* WE NEED TO DO IT STRTOLOWER */
class tx_Extbase_Persistence_Hook_TCEMainValueObjectUpdater {
	public function processDatamap_preProcessFieldArray(&$incomingFieldArray, $table, &$id, $tcemain) {
		global $TCA;
		if (isset($TCA[$table]['ctrl']['objectType']) && $TCA[$table]['ctrl']['objectType'] === 'ValueObject') {
			$isNewRecord = !t3lib_div::testInt($id);

			$uid = $this->findUid($incomingFieldArray, $table);
			if ($uid !== NULL) {
				// FOUND a UID.
				if ($isNewRecord) {
					// re-map the insertion to an update!
					$tcemain->substNEWwitIDs[$id] = (int)$uid;
					$id = (int)$uid;
					unset($incomingFieldArray['pid']);
				}
				//var_dump($incomingFieldArray, $table, $id, $uid);
				//
				//$incomingFieldArray['uid'] = $uid;
			} else {
				// We did not find an already existing entry with the same values in the DB
				// Thus, the entry can safely be created if $isNewRecord.
				// if record is not new, and we did not find any of these values in the DB, we can just leave the record as is.
				if (!$isNewRecord) {

				}
			}

		}
	}

	protected function findUid($incomingFieldArray, $table) {
		$whereClauseArray = array();
		unset($incomingFieldArray['uid']);
		foreach ($incomingFieldArray as $key => $value) {
			$whereClauseArray[] = $key . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($value, 'dummy');
		}
		$resultArray = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', $table, implode(' AND ', $whereClauseArray));

		if (count($resultArray)) {
			return $resultArray[0];
		}
		return NULL;
	}
}
?>