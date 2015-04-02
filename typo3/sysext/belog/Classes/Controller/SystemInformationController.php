<?php
namespace TYPO3\CMS\Belog\Controller;

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

use TYPO3\CMS\Belog\Domain\Model\Constraint;

/**
 * Count newest exceptions for the system information menu
 */
class SystemInformationController extends AbstractController {

	/**
	 * Modifies the SystemInformation array
	 *
	 * @return NULL|array
	 */
	public function appendMessage() {
		$constraint = $this->getConstraintFromBeUserData();
		if ($constraint === NULL) {
			$constraint = $this->objectManager->get(Constraint::class);
		}

		$this->setStartAndEndTimeFromTimeSelector($constraint);
		// we can't use the extbase repository here as the required TypoScript may not be parsed yet
		$count = $this->getDatabaseConnection()->exec_SELECTcountRows('error', 'sys_log', 'tstamp >= ' . $constraint->getStartTimestamp() . ' AND tstamp <= ' . $constraint->getEndTimestamp() . ' AND error IN(-1,1,2)');

		if ($count > 0) {
			return array(
				array(
					'count' => $count,
					'status' => \TYPO3\CMS\Backend\Toolbar\Enumeration\InformationStatus::STATUS_ERROR,
					'text' => sprintf(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('systemmessage.errorsInPeriod', 'belog'), $count)
				)
			);
		}
		return NULL;
	}

	/**
	 * Get module states (the constraint object) from user data
	 *
	 * @return \TYPO3\CMS\Belog\Domain\Model\Constraint|NULL
	 */
	protected function getConstraintFromBeUserData() {
		$serializedConstraint = $GLOBALS['BE_USER']->getModuleData(ToolsController::class);
		if (!is_string($serializedConstraint) || empty($serializedConstraint)) {
			return NULL;
		}
		return @unserialize($serializedConstraint);
	}

	/**
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}
}
