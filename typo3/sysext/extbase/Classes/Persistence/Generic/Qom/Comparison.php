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
 * Filters node-tuples based on the outcome of a binary operation.
 *
 * For any comparison, operand2 always evaluates to a scalar value. In contrast,
 * operand1 may evaluate to an array of values (for example, the value of a multi-valued
 * property), in which case the comparison is separately performed for each element
 * of the array, and the Comparison constraint is satisfied as a whole if the
 * comparison against any element of the array is satisfied.
 *
 * If operand1 and operand2 evaluate to values of different property types, the
 * value of operand2 is converted to the property type of the value of operand1.
 * If the type conversion fails, the query is invalid.
 *
 * If operator is not supported for the property type of operand1, the query is invalid.
 *
 * If operand1 evaluates to null (for example, if the operand evaluates the value
 * of a property which does not exist), the constraint is not satisfied.
 *
 * The JCR_OPERATOR_EQUAL_TO operator is satisfied only if the value of operand1
 * equals the value of operand2.
 *
 * The JCR_OPERATOR_NOT_EQUAL_TO operator is satisfied unless the value of
 * operand1 equals the value of operand2.
 *
 * The JCR_OPERATOR_LESSS_THAN operator is satisfied only if the value of
 * operand1 is ordered before the value of operand2.
 *
 * The JCR_OPERATOR_LESS_THAN_OR_EQUAL_TO operator is satisfied unless the value
 * of operand1 is ordered after the value of operand2.
 *
 * The JCR_OPERATOR_GREATER_THAN operator is satisfied only if the value of
 * operand1 is ordered after the value of operand2.
 *
 * The JCR_OPERATOR_GREATER_THAN_OR_EQUAL_TO operator is satisfied unless the
 * value of operand1 is ordered before the value of operand2.
 *
 * The JCR_OPERATOR_LIKE operator is satisfied only if the value of operand1
 * matches the pattern specified by the value of operand2, where in the pattern:
 * the character "%" matches zero or more characters, and
 * the character "_" (underscore) matches exactly one character, and
 * the string "\x" matches the character "x", and
 * all other characters match themselves.
 */
class Comparison implements \TYPO3\CMS\Extbase\Persistence\Generic\Qom\ComparisonInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Qom\DynamicOperandInterface
	 */
	protected $operand1;

	/**
	 * @var integer
	 */
	protected $operator;

	/**
	 * @var mixed
	 */
	protected $operand2;

	/**
	 * Constructs this Comparison instance
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\DynamicOperandInterface $operand1
	 * @param integer $operator one of \TYPO3\CMS\Extbase\Persistence\QueryInterface.OPERATOR_*
	 * @param mixed $operand2
	 */
	public function __construct(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\DynamicOperandInterface $operand1, $operator, $operand2) {
		$this->operand1 = $operand1;
		$this->operator = $operator;
		$this->operand2 = $operand2;
	}

	/**
	 * Gets the first operand.
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\DynamicOperandInterface the operand; non-null
	 */
	public function getOperand1() {
		return $this->operand1;
	}

	/**
	 * Gets the operator.
	 *
	 * @return string one of \TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelConstantsInterface.JCR_OPERATOR_*
	 */
	public function getOperator() {
		return $this->operator;
	}

	/**
	 * Gets the second operand.
	 *
	 * @return mixed the operand; non-null
	 */
	public function getOperand2() {
		return $this->operand2;
	}
}

?>