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
 * Evaluates to the upper-case string value (or values, if multi-valued) of
 * operand.
 *
 * If operand does not evaluate to a string value, its value is first converted
 * to a string.
 *
 * If operand evaluates to null, the UpperCase operand also evaluates to null.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class UpperCase implements \TYPO3\CMS\Extbase\Persistence\Generic\Qom\UpperCaseInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Qom\DynamicOperandInterface
	 */
	protected $operand;

	/**
	 * Constructs this UpperCase instance
	 *
	 * @param DynamicOperandInterface $operand
	 */
	public function __construct(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\DynamicOperandInterface $operand) {
		$this->operand = $operand;
	}

	/**
	 * Gets the operand whose value is converted to a upper-case string.
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\DynamicOperandInterface the operand; non-null
	 */
	public function getOperand() {
		return $this->operand;
	}
}

?>