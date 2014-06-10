<?php
namespace TYPO3\CMS\Core\Tests\Functional\Framework\Constraint\RequestSection;

/***************************************************************
 * Copyright notice
 *
 * (c) 2014 Oliver Hader <oliver.hader@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Tests\Functional\Framework\Frontend\ResponseSection;

/**
 * Model of frontend response
 */
class StructureHasRecordConstraint extends AbstractStructureRecordConstraint {

	/**
	 * @param ResponseSection $responseSection
	 * @return bool
	 */
	protected function matchesSection(ResponseSection $responseSection) {
		$nonMatchingVariants = array();
		$remainingRecordVariants = array();

		foreach ($responseSection->findStructures($this->recordIdentifier, $this->recordField) as $path => $structure) {
			if (empty($structure) || !is_array($structure)) {
				$this->sectionFailures[$responseSection->getIdentifier()] = 'No records found in "' . $path . '"';
				return FALSE;
			}

			$remainingRecords = array();
			$nonMatchingValues = $this->getNonMatchingValues($structure);

			if ($this->strict) {
				$remainingRecords = $this->getRemainingRecords($structure);
			}

			if (empty($nonMatchingValues) && (!$this->strict || empty($remainingRecords))) {
				return TRUE;
			}

			if (!empty($nonMatchingValues)) {
				$nonMatchingVariants[$path] = $nonMatchingValues;
			}
			if ($this->strict && !empty($remainingRecords)) {
				$remainingRecordVariants[$path] = $remainingRecords;
			}
		}

		$failureMessage = '';

		if (!empty($nonMatchingVariants)) {
			$failureMessage .= 'Could not assert all values for "' . $this->table . '.' . $this->field . '"' . LF;
			foreach ($nonMatchingVariants as $path => $nonMatchingValues) {
				$failureMessage .= '  * Not found in "' . $path . '": ' . implode(', ', $nonMatchingValues) . LF;
			}
		}

		if (!empty($remainingRecordVariants)) {
			$failureMessage .= 'Found remaining records for "' . $this->table . '.' . $this->field . '"' . LF;
			foreach ($remainingRecordVariants as $path => $remainingRecords) {
				$failureMessage .= '  * Found in "' . $path . '": ' . implode(', ', array_keys($remainingRecords)) . LF;
			}
		}

		$this->sectionFailures[$responseSection->getIdentifier()] = $failureMessage;
		return FALSE;
	}

	/**
	 * Returns a string representation of the constraint.
	 *
	 * @return string
	 */
	public function toString() {
		return 'structure has record';
	}

}
