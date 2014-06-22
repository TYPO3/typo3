<?php
namespace TYPO3\CMS\Beuser\ViewHelpers;

/**
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

/**
 * Views sprite icon for a record (object)
 *
 * @author Felix Kopp <felix-source@phorax.com>
 */
class SpriteIconForRecordViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper {

	/**
	 * Displays spriteIcon for database table and object
	 *
	 * @param string $table
	 * @param object $object
	 * @return string
	 * @see \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord($table, $row)
	 */
	public function render($table, $object) {
		if (!is_object($object) || !method_exists($object, 'getUid')) {
			return '';
		}
		$row = array(
			'uid' => $object->getUid(),
			'startTime' => FALSE,
			'endTime' => FALSE
		);
		if (method_exists($object, 'getIsDisabled')) {
			$row['disable'] = $object->getIsDisabled();
		}
		if ($table === 'be_users' && $object instanceof \TYPO3\CMS\Beuser\Domain\Model\BackendUser) {
			$row['admin'] = $object->getIsAdministrator();
		}
		if (method_exists($object, 'getStartDateAndTime')) {
			$row['startTime'] = $object->getStartDateAndTime();
		}
		if (method_exists($object, 'getEndDateAndTime')) {
			$row['endTime'] = $object->getEndDateAndTime();
		}
		return \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord($table, $row);
	}

}
