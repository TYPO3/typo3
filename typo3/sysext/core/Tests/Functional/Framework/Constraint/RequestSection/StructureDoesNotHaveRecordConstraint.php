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
class StructureDoesNotHaveRecordConstraint extends AbstractStructureRecordConstraint {

	/**
	 * @param ResponseSection $responseSection
	 * @return bool
	 */
	protected function matchesSection(ResponseSection $responseSection) {
		$matchingVariants = array();

		foreach ($responseSection->findStructures($this->recordIdentifier, $this->recordField) as $path => $structure) {
			if (empty($structure) || !is_array($structure)) {
				$this->sectionFailures[$responseSection->getIdentifier()] = 'No records found in "' . $path . '"';
				return FALSE;
			}

			$nonMatchingValues = $this->getNonMatchingValues($structure);
			$matchingValues = array_diff($this->values, $nonMatchingValues);

			if (!empty($matchingValues)) {
				$matchingVariants[$path] = $matchingValues;
			}
		}

		if (empty($matchingVariants)) {
			return TRUE;
		}

		$matchingMessage = '';
		foreach ($matchingVariants as $path => $matchingValues) {
			$matchingMessage .= '  * Found in "' . $path . '": ' . implode(', ', $matchingValues);
		}

		$this->sectionFailures[$responseSection->getIdentifier()] = 'Could not assert not having values for "' . $this->table . '.' . $this->field . '"' . LF . $matchingMessage;
		return FALSE;
	}

	/**
	 * Returns a string representation of the constraint.
	 *
	 * @return string
	 */
	public function toString() {
		return 'structure does not have record';
	}

}
