<?php
namespace TYPO3\CMS\Beuser\ViewHelpers;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Felix Kopp <felix-source@phorax.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

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
		if ($table === 'be_users' && get_class($object) === 'TYPO3\\CMS\\Beuser\\Domain\\Model\\BackendUser') {
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

?>