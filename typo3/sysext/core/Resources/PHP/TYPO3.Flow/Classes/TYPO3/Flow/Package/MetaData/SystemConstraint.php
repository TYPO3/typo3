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
 * System constraint meta model
 *
 */
class SystemConstraint extends \TYPO3\Flow\Package\MetaData\AbstractConstraint {

	/**
	 * The type for a system scope constraint (e.g. "Memory")
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Meta data system constraint constructor
	 *
	 * @param string $constraintType
	 * @param string $type
	 * @param string $value
	 * @param string $minVersion
	 * @param string $maxVersion
	 */
	public function __construct($constraintType, $type, $value = NULL, $minVersion = NULL, $maxVersion = NULL) {
		if (!strlen($value)) {
			$value = NULL;
		}
		parent::__construct($constraintType, $value, $minVersion, $maxVersion);
		$this->type = $type;
	}

	/**
	 * @return string The system constraint type
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @return string The constraint scope
	 * @see \TYPO3\Flow\Package\MetaData\Constraint\getConstraintScope()
	 */
	public function getConstraintScope() {
		return \TYPO3\Flow\Package\MetaDataInterface::CONSTRAINT_SCOPE_SYSTEM;
	}
}
?>