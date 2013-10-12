<?php
namespace TYPO3\Flow\Package\MetaData;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Constraint meta data model
 *
 */
abstract class AbstractConstraint {

	/**
	 * One of depends, conflicts or suggests
	 * @var string
	 */
	protected $constraintType;

	/**
	 * The constraint name or value
	 * @var string
	 */
	protected $value;

	/**
	 * Meta data constraint constructor
	 *
	 * @param string $constraintType
	 * @param string $value
	 * @param string $minVersion
	 * @param string $maxVersion
	 */
	public function __construct($constraintType, $value, $minVersion = NULL, $maxVersion = NULL) {
		$this->constraintType = $constraintType;
		$this->value = $value;
		$this->minVersion = $minVersion;
		$this->maxVersion = $maxVersion;
	}

	/**
	 * @return string The constraint name or value
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * @return string The constraint type (depends, conflicts, suggests)
	 */
	public function getConstraintType() {
		return $this->constraintType;
	}

	/**
	 * @return string The constraint scope (package, system)
	 */
	abstract public function getConstraintScope();
}
?>