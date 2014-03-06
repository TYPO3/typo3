<?php
namespace TYPO3\CMS\Install\Status;

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
 * Utility methods to handle status objects. Provides some helper
 * methods to filter, sort and render status objects.
 */
class StatusUtility {

	/**
	 * Order status objects by severity
	 *
	 * @param array<\TYPO3\CMS\Install\Status\StatusInterface> $statusObjects Status objects in random order
	 * @return array With sub arrays by severity
	 * @throws Exception
	 */
	public function sortBySeverity(array $statusObjects = array()) {
		$orderedStatus = array(
			'alert' => $this->filterBySeverity($statusObjects, 'alert'),
			'error' => $this->filterBySeverity($statusObjects, 'error'),
			'warning' => $this->filterBySeverity($statusObjects, 'warning'),
			'ok' => $this->filterBySeverity($statusObjects, 'ok'),
			'information' => $this->filterBySeverity($statusObjects, 'information'),
			'notice' => $this->filterBySeverity($statusObjects, 'notice'),
		);
		return $orderedStatus;
	}

	/**
	 * Filter a list of status objects by severity
	 *
	 * @param array $statusObjects Given list of status objects
	 * @param string $severity Severity identifier
	 * @throws Exception
	 * @return array List of status objects with given severity
	 */
	public function filterBySeverity(array $statusObjects = array(), $severity = 'ok') {
		$filteredObjects = array();
		/** @var $status StatusInterface */
		foreach ($statusObjects as $status) {
			if (!$status instanceof StatusInterface) {
				throw new Exception(
					'Object must implement StatusInterface',
					1366919442
				);
			}
			if ($status->getSeverity() === $severity) {
				$filteredObjects[] = $status;
			}
		}
		return $filteredObjects;
	}
}
