<?php
namespace TYPO3\CMS\Backend\Form\FormDataProvider;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handle "databaseRow" select values
 */
class TcaSelectValues implements FormDataProviderInterface {

	/**
	 * Validate and sanitize database row values of select elements
	 * Creates an array out of databaseRow[selectField] values.
	 *
	 * @param array $result
	 * @return array
	 * @todo: It might be better to merge this one with TcaSelectItems again. For instance, the validation for
	 * @todo: == 0 or == 1 below could be done between the "plain" processing methods of TcaSelectItems and before
	 * @todo: the db fetching methods and would be more solid this way.
	 */
	public function addData(array $result) {
		foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
			if (empty($fieldConfig['config']['type']) || $fieldConfig['config']['type'] !== 'select') {
				continue;
			}
			$currentDatabaseValues = array_key_exists($fieldName, $result['databaseRow']) ? $result['databaseRow'][$fieldName] : '';
			$currentDatabaseValuesArray = GeneralUtility::trimExplode(',', $currentDatabaseValues);
			$newDatabaseValueArray = array();
			if (isset($fieldConfig['config']['foreign_table']) && !empty($fieldConfig['config']['foreign_table'])
				&& isset($fieldConfig['config']['MM']) && !empty($fieldConfig['config']['MM'])
			) {
				// MM relation
				/** @var RelationHandler $relationHandler */
				$relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
				$relationHandler->registerNonTableValues = !empty($fieldConfig['config']['allowNonIdValues']);
				$relationHandler->start(
					$currentDatabaseValues,
					$fieldConfig['config']['foreign_table'],
					$fieldConfig['config']['MM'],
					$result['databaseRow']['uid'],
					$result['tableName'],
					$fieldConfig['config']
				);
				$newDatabaseValueArray = $relationHandler->getValueArray();
			} elseif (isset($fieldConfig['config']['foreign_table']) && !empty($fieldConfig['config']['foreign_table'])) {
				// Non MM relation
				// If not dealing with MM relations, use default live uid, not versioned uid for record relations
				$uid = $this->getLiveUid($result);
				/** @var RelationHandler $relationHandler */
				$relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
				$relationHandler->registerNonTableValues = !empty($fieldConfig['config']['allowNonIdValues']);
				$relationHandler->start(
					$currentDatabaseValues,
					$fieldConfig['config']['foreign_table'],
					'',
					$uid,
					$result['tableName'],
					$fieldConfig['config']
				);
				$connectedUidsFromRelationHandler = $relationHandler->getValueArray();
				foreach ($currentDatabaseValuesArray as $aCurrentDatabaseValue) {
					$aCurrentDatabaseValue = (int)$aCurrentDatabaseValue;
					if (in_array($aCurrentDatabaseValue, $connectedUidsFromRelationHandler)) {
						$newDatabaseValueArray[] = $aCurrentDatabaseValue;
					}
					// Values 0 and -1 can not come from db relations but may be set as default additional items. Keep them.
					// Used for instance in tt_content sys_language_uid
					// @todo: Test missing for this case
					if ($aCurrentDatabaseValue == '0' || $aCurrentDatabaseValue == '-1') {
						$newDatabaseValueArray[] = $aCurrentDatabaseValue;
					}
				}
			} else {
				$newDatabaseValueArray = $currentDatabaseValuesArray;
			}
			$result['databaseRow'][$fieldName] = $newDatabaseValueArray;
		}

		return $result;
	}

	/**
	 * Gets the record uid of the live default record. If already
	 * pointing to the live record, the submitted record uid is returned.
	 *
	 * @param array $result Result array
	 * @return int
	 * @throws \UnexpectedValueException
	 */
	protected function getLiveUid(array $result) {
		$table = $result['tableName'];
		$row = $result['databaseRow'];
		$uid = $row['uid'];
		if (!empty($result['processedTca']['ctrl']['versioningWS'])
			&& $result['pid'] === -1
		) {
			if (empty($row['t3ver_oid'])) {
				throw new \UnexpectedValueException(
					'No t3ver_oid found for record ' . $row['uid'] . ' on table ' . $table,
					1440066481
				);
			}
			$uid = $row['t3ver_oid'];
		}
		return $uid;
	}

}
