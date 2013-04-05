<?php
namespace TYPO3\CMS\Install\Report;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Provides an environment status report
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class EnvironmentStatusReport implements \TYPO3\CMS\Reports\StatusProviderInterface {

	/**
	 * Compile environment status report
	 *
	 * @throws \TYPO3\CMS\Install\Exception
	 * @return array<\TYPO3\CMS\Reports\Status>
	 */
	public function getStatus() {
		/** @var $statusCheck \TYPO3\CMS\Install\SystemEnvironment\Check */
		$statusCheck = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Install\\SystemEnvironment\\Check');
		$statusObjects = $statusCheck->getStatus();

		$reportStatusTypes = array(
			'error' => array(),
			'warning' => array(),
			'ok' => array(),
			'information' => array(),
			'notice' => array(),
		);

		/** @var $statusObject \TYPO3\CMS\Install\SystemEnvironment\AbstractStatus */
		foreach ($statusObjects as $statusObject) {
			$severityIdentifier = $statusObject->getSeverity();
			if (empty($severityIdentifier) || !is_array($reportStatusTypes[$severityIdentifier])) {
				throw new \TYPO3\CMS\Install\Exception('Unknown reports severity type', 1362602560);
			}
			$reportStatusTypes[$severityIdentifier][] = $statusObject;
		}

		$statusArray = array();
		foreach ($reportStatusTypes as $type => $statusObjects) {
			$value = count($statusObjects);
			if ($value > 0) {
				$pathToXliff = 'LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf';
				// Map information type to abbreviation which is used in \TYPO3\CMS\Reports\Status class
				if ($type === 'information') {
					$type = 'info';
				}
				$message = $GLOBALS['LANG']->sL($pathToXliff . ':environment.status.message.' . $type);
				$severity = constant('\TYPO3\CMS\Reports\Status::' . strtoupper($type));
				$statusArray[] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
					'TYPO3\\CMS\\Reports\\Status',
					$GLOBALS['LANG']->sL($pathToXliff . ':environment.status.title'),
					sprintf($GLOBALS['LANG']->sL($pathToXliff . ':environment.status.value'), $value),
					$message,
					$severity
				);
			}
		}

		return $statusArray;
	}
}

?>