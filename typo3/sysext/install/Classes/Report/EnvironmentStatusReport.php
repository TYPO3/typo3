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
			'ERROR' => array(),
			'WARNING' => array(),
			'OK' => array(),
			'INFO' => array(),
			'NOTICE' => array(),
		);

		foreach ($statusObjects as $statusObject) {
			$className = get_class($statusObject);
			// Uppercase last part of class name, without last 6 chars:
			// TYPO3\CMS\Install\SystemEnvironment\ErrorStatus -> ERROR
			$severityIdentifier = strtoupper(substr(array_pop(explode('\\', $className)), 0, -6));
			if (!is_array($reportStatusTypes[$severityIdentifier])) {
				throw new \TYPO3\CMS\Install\Exception('Unknown reports severity type', 1362602560);
			}
			$reportStatusTypes[$severityIdentifier][] = $statusObject;
		}

		$statusArray = array();
		foreach($reportStatusTypes as $type => $statusObjects) {
			$value = count($statusObjects);
			if ($value > 0) {
				$pathToXlif = 'LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf';
				$message = $GLOBALS['LANG']->sL($pathToXlif . ':environment.status.message.warning');
				$severity = constant('\TYPO3\CMS\Reports\Status::' . $type);
				$statusArray[] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
					'TYPO3\\CMS\\Reports\\Status',
					$GLOBALS['LANG']->sL($pathToXlif . ':environment.status.title'),
					sprintf($GLOBALS['LANG']->sL($pathToXlif . ':environment.status.value'), $value),
					$message,
					$severity
				);
			}
		}

		return $statusArray;
	}
}

?>