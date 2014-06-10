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
abstract class AbstractRecordConstraint extends \PHPUnit_Framework_Constraint {

	/**
	 * @var array
	 */
	protected $sectionFailures = array();

	/**
	 * @var string
	 */
	protected $table;

	/**
	 * @var string
	 */
	protected $field;

	/**
	 * @var bool
	 */
	protected $strict = FALSE;

	/**
	 * @var array
	 */
	protected $values;

	public function setTable($table) {
		$this->table = $table;
		return $this;
	}

	public function setField($field) {
		$this->field = $field;
		return $this;
	}

	public function setValues() {
		$values = func_get_args();
		$this->values = $values;
		return $this;
	}

	public function setStrict($strict) {
		$this->strict = (bool)$strict;
		return $this;
	}

	/**
	 * Evaluates the constraint for parameter $other. Returns true if the
	 * constraint is met, false otherwise.
	 *
	 * @param array|ResponseSection|ResponseSection[] $other ResponseSections to evaluate
	 * @return bool
	 */
	protected function matches($other) {
		if (is_array($other)) {
			$success = NULL;
			foreach ($other as $item) {
				$currentSuccess = $this->matchesSection($item);
				$success = ($success === NULL ? $currentSuccess : $success || $currentSuccess);
			}
			return !empty($success);
		} else {
			return $this->matchesSection($other);
		}
	}

	/**
	 * @param ResponseSection $responseSection
	 * @return bool
	 */
	abstract protected function matchesSection(ResponseSection $responseSection);

	/**
	 * @param array $records
	 * @return array
	 */
	protected function getNonMatchingValues(array $records) {
		$values = $this->values;

		foreach ($records as $recordIdentifier => $recordData) {
			if (strpos($recordIdentifier, $this->table . ':') !== 0) {
				continue;
			}

			if (($foundValueIndex = array_search($recordData[$this->field], $values)) !== FALSE) {
				unset($values[$foundValueIndex]);
			}
		}

		return $values;
	}

	/**
	 * @param array $records
	 * @return array
	 */
	protected function getRemainingRecords(array $records) {
		$values = $this->values;

		foreach ($records as $recordIdentifier => $recordData) {
			if (strpos($recordIdentifier, $this->table . ':') !== 0) {
				unset($records[$recordIdentifier]);
				continue;
			}

			if (($foundValueIndex = array_search($recordData[$this->field], $values)) !== FALSE) {
				unset($values[$foundValueIndex]);
				unset($records[$recordIdentifier]);
			}
		}

		return $records;
	}

	/**
	 * Returns the description of the failure
	 *
	 * The beginning of failure messages is "Failed asserting that" in most
	 * cases. This method should return the second part of that sentence.
	 *
	 * @param mixed $other Evaluated value or object.
	 * @return string
	 */
	protected function failureDescription($other) {
		return $this->toString();
	}

	/**
	 * Return additional failure description where needed
	 *
	 * The function can be overridden to provide additional failure
	 * information like a diff
	 *
	 * @param mixed $other Evaluated value or object.
	 * @return string
	 */
	protected function additionalFailureDescription($other) {
		$failureDescription = '';
		foreach ($this->sectionFailures as $sectionIdentifier => $sectionFailure) {
			$failureDescription .= '* Section "' . $sectionIdentifier . '": ' . $sectionFailure . LF;
		}
		return $failureDescription;
	}

}
