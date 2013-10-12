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
 * Package constraint meta model
 *
 */
class PackageConstraint extends \TYPO3\Flow\Package\MetaData\AbstractConstraint {

	/**
	 * @return string The constraint scope
	 * @see \TYPO3\Flow\Package\MetaData\Constraint::getConstraintScope()
	 */
	public function getConstraintScope() {
		return \TYPO3\Flow\Package\MetaDataInterface::CONSTRAINT_SCOPE_PACKAGE;
	}
}
?>