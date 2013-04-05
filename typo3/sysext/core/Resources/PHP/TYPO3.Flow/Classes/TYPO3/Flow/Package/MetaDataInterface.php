<?php
namespace TYPO3\Flow\Package;

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
 * Interface for TYPO3 Package MetaData information
 *
 */
interface MetaDataInterface {

	const CONSTRAINT_TYPE_DEPENDS = 'depends';
	const CONSTRAINT_TYPE_CONFLICTS = 'conflicts';
	const CONSTRAINT_TYPE_SUGGESTS = 'suggests';

	const PARTY_TYPE_PERSON = 'person';
	const PARTY_TYPE_COMPANY = 'company';

	const CONSTRAINT_SCOPE_PACKAGE = 'package';
	const CONSTRAINT_SCOPE_SYSTEM = 'system';

	/**
	 * @return string The package key
	 */
	public function getPackageKey();

	/**
	 * @return string The package version
	 */
	public function getVersion();

	/**
	 * @return string The package description
	 */
	public function getDescription();

	/**
	 * @return Array of string The package categories
	 */
	public function getCategories();

	/**
	 * @return Array of TYPO3\Flow\Package\MetaData\Party The package parties
	 */
	public function getParties();

	/**
	 * @param string $constraintType Type of the constraints to get: CONSTRAINT_TYPE_*
	 * @return Array of TYPO3\Flow\Package\MetaData\Constraint Package constraints
	 */
	public function getConstraintsByType($constraintType);

	/**
	 * Get all constraints
	 *
	 * @return array An array of array of \TYPO3\Flow\Package\MetaData\Constraint Package constraints
	 */
	public function getConstraints();
}
?>