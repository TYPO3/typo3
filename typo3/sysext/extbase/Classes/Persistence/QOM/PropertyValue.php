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
 * Evaluates to the value (or values, if multi-valued) of a property.
 *
 * If, for a node-tuple, the selector node does not have a property named property,
 * the operand evaluates to null.
 *
 * The query is invalid if:
 *
 * selector is not the name of a selector in the query, or
 * property is not a syntactically valid JCR name.
 *
 * @package Extbase
 * @subpackage Persistence\QOM
 * @version $Id: PropertyValue.php 1729 2009-11-25 21:37:20Z stucki $
 * @scope prototype
 */
class Tx_Extbase_Persistence_QOM_PropertyValue extends Tx_Extbase_Persistence_QOM_DynamicOperand implements Tx_Extbase_Persistence_QOM_PropertyValueInterface {

	/**
	 * @var string
	 */
	protected $selectorName;

	/**
	 * @var string
	 */
	protected $propertyName;

	/**
	 * Constructs this PropertyValue instance
	 *
	 * @param string $propertyName
	 * @param string $selectorName
	 */
	public function __construct($propertyName, $selectorName = '') {
		$this->propertyName = $propertyName;
		$this->selectorName = $selectorName;
	}

	/**
	 * Gets the name of the selector against which to evaluate this operand.
	 *
	 * @return string the selector name; non-null
	 */
	public function getSelectorName() {
		return $this->selectorName;
	}

	/**
	 * Gets the name of the property.
	 *
	 * @return string the property name; non-null
	 */
	public function getPropertyName() {
		return $this->propertyName;
	}

}

?>