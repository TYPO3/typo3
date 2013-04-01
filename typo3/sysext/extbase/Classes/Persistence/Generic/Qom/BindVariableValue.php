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
 * Evaluates to the value of a bind variable.
 */
class BindVariableValue extends \TYPO3\CMS\Extbase\Persistence\Generic\Qom\StaticOperand implements \TYPO3\CMS\Extbase\Persistence\Generic\Qom\BindVariableValueInterface {

	/**
	 * @var string
	 */
	protected $variableName;

	/**
	 * Constructs this BindVariableValue instance
	 *
	 * @param string $variableName
	 */
	public function __construct($variableName) {
		$this->variableName = $variableName;
	}

	/**
	 * Fills an array with the names of all bound variables in the operand
	 *
	 * @param array &$boundVariables
	 * @return void
	 */
	public function collectBoundVariableNames(&$boundVariables) {
		$boundVariables[$this->variableName] = NULL;
	}

	/**
	 * Gets the name of the bind variable.
	 *
	 * @return string the bind variable name; non-null
	 */
	public function getBindVariableName() {
		return $this->variableName;
	}
}

?>