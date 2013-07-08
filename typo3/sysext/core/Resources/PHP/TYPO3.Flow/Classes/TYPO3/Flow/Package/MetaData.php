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
 * The default TYPO3 Package MetaData implementation
 *
 */
class MetaData implements \TYPO3\Flow\Package\MetaDataInterface {

	/**
	 * @var array
	 */
	protected static $CONSTRAINT_TYPES = array(self::CONSTRAINT_TYPE_DEPENDS, self::CONSTRAINT_TYPE_CONFLICTS, self::CONSTRAINT_TYPE_SUGGESTS);

	/**
	 * @var string
	 */
	protected $packageKey;

	/**
	 * The version number
	 * @var string
	 */
	protected $version;

	/**
	 * Package title
	 * @var string
	 */
	protected $title;

	/**
	 * Package description
	 * @var string
	 */
	protected $description;

	/**
	 * Package categories as string
	 * @var array
	 */
	protected $categories = array();

	/**
	 * Package parties (person, company)
	 * @var array
	 */
	protected $parties = array();

	/**
	 * constraints by constraint type (depends, conflicts, suggests)
	 * @var array
	 */
	protected $constraints = array();

	/**
	 * Get all available constraint types
	 *
	 * @return array All constraint types
	 */
	public function getConstraintTypes() {
		return self::$CONSTRAINT_TYPES;
	}

	/**
	 * Package metadata constructor
	 *
	 * @param string $packageKey The package key
	 */
	public function __construct($packageKey) {
		$this->packageKey = $packageKey;
	}

	/**
	 * @return string The package key
	 */
	public function getPackageKey() {
		return $this->packageKey;
	}

	/**
	 * @return string The package version
	 */
	public function getVersion() {
		return $this->version;
	}

	/**
	 * @param string $version The package version to set
	 * @return void
	 */
	public function setVersion($version) {
		$this->version = $version;
	}

	/**
	 * @return string The package description
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @param string $description The package description to set
	 * @return void
	 */
	public function setDescription($description) {
		$this->description = $description;
	}

	/**
	 * @return Array of string The package categories
	 */
	public function getCategories() {
		return $this->categories;
	}

	/**
	 * Adds a package category
	 *
	 * @param string $category
	 * @return void
	 */
	public function addCategory($category) {
		$this->categories[] = $category;
	}

	/**
	 * @return Array of TYPO3\Flow\Package\MetaData\AbstractParty The package parties
	 */
	public function getParties() {
		return $this->parties;
	}

	/**
	 * Add a party
	 *
	 * @param \TYPO3\Flow\Package\MetaData\AbstractParty $party
	 * @return void
	 */
	public function addParty(\TYPO3\Flow\Package\MetaData\AbstractParty $party) {
		$this->parties[] = $party;
	}

	/**
	 * Get all constraints
	 *
	 * @return array Package constraints
	 */
	public function getConstraints() {
		return $this->constraints;
	}

	/**
	 * Get the constraints by type
	 *
	 * @param string $constraintType Type of the constraints to get: CONSTRAINT_TYPE_*
	 * @return array Package constraints
	 */
	public function getConstraintsByType($constraintType) {
		if (!isset($this->constraints[$constraintType])) return array();
		return $this->constraints[$constraintType];
	}

	/**
	 * Add a constraint
	 *
	 * @param \TYPO3\Flow\Package\MetaData\AbstractConstraint $constraint The constraint to add
	 * @return void
	 */
	public function addConstraint(\TYPO3\Flow\Package\MetaData\AbstractConstraint $constraint) {
		$this->constraints[$constraint->getConstraintType()][] = $constraint;
	}
}
?>