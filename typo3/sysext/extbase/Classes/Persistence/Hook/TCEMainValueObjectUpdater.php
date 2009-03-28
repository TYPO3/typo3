<?php
/* WE NEED TO DO IT STRTOLOWER */
class tx_ExtBase_Persistence_Hook_TCEMainValueObjectUpdater {
	public function processDatamap_preProcessFieldArray(&$incomingFieldArray, $table, &$id, $tcemain) {
		global $TCA;
		if (isset($TCA[$table]['ctrl']['objectType']) && $TCA[$table]['ctrl']['objectType'] === 'ValueObject') {
			$uid = $this->findUid($incomingFieldArray, $table);
			if ($uid !== NULL) {
				//var_dump($incomingFieldArray, $table, $id, $uid);
				$id = (int)$uid;
				$incomingFieldArray['uid'] = $uid;
			} else {
				// $id = 'NEW' . $uid;
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