<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic\Qom;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
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
 */
class PropertyValue extends \TYPO3\CMS\Extbase\Persistence\Generic\Qom\DynamicOperand implements \TYPO3\CMS\Extbase\Persistence\Generic\Qom\PropertyValueInterface {

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