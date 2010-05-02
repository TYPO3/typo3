<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
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
 * Tests whether the value of a property in a first selector is equal to the value of a
 * property in a second selector.
 * A node-tuple satisfies the constraint only if: the selector1Name node has a property named property1Name, and
 * the selector2Name node has a property named property2Name, and
 * the value of property property1Name is equal to the value of property property2Name.
 *
 * @package Extbase
 * @subpackage Persistence\QOM
 * @version $Id: EquiJoinCondition.php 1729 2009-11-25 21:37:20Z stucki $
 * @scope prototype
 */
class Tx_Extbase_Persistence_QOM_EquiJoinCondition implements Tx_Extbase_Persistence_QOM_JoinConditionInterface {

	/**
	 * @var string
	 */
	protected $selector1Name;

	/**
	 * @var string
	 */
	protected $property1Name;

	/**
	 * @var string
	 */
	protected $selector2Name;

	/**
	 * @var string
	 */
	protected $property2Name;

	/**
	 * Constructs this EquiJoinCondition instance
	 *
	 * @param string $selector1Name the name of the first selector; non-null
	 * @param string $property1Name the property name in the first selector; non-null
	 * @param string $selector2Name the name of the second selector; non-null
	 * @param string $property2Name the property name in the second selector; non-null
	 */
	public function __construct($selector1Name, $property1Name, $selector2Name, $property2Name) {
		// TODO Test for selector1Name = selector2Name -> exception
		$this->selector1Name = $selector1Name;
		$this->property1Name = $property1Name;
		$this->selector2Name = $selector2Name;
		$this->property2Name = $property2Name;
	}

	/**
	 * Gets the name of the first selector.
	 *
	 * @return string the selector name; non-null
	 */
	public function getSelector1Name() {
		return $this->selector1Name;
	}

	/**
	 * Gets the name of the first property.
	 *
	 * @return string the property name; non-null
	 */
	public function getProperty1Name() {
		return $this->property1Name;
	}

	/**
	 * Gets the name of the second selector.
	 *
	 * @return string the selector name; non-null
	 */
	public function getSelector2Name() {
		return $this->selector2Name;
	}

	/**
	 * Gets the name of the second property.
	 *
	 * @return string the property name; non-null
	 */
	public function getProperty2Name() {
		return $this->property2Name;
	}

}

?>