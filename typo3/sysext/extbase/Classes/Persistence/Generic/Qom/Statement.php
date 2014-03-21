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
 *  A copy is found in the text file GPL.txt and important notices to the license
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
 * A statement acting as a constraint.
 */
class Statement implements ConstraintInterface {

	/**
	 * @var string|\TYPO3\CMS\Core\Database\PreparedStatement
	 */
	protected $statement;

	/**
	 * @var array
	 */
	protected $boundVariables = array();

	/**
	 * Constructs the Statement instance
	 *
	 * @param string|\TYPO3\CMS\Core\Database\PreparedStatement $statement The statement as sql string or TYPO3\CMS\Core\Database\PreparedStatement
	 * @param array $boundVariables An array of variables to bind to the statement, only to be used with preparedStatement
	 */
	public function __construct($statement, array $boundVariables = array()) {
		// @deprecated since 6.2, using $boundVariables without preparedStatement will be removed in two versions
		if (
			!empty($boundVariables)
			&& !($statement instanceof \TYPO3\CMS\Core\Database\PreparedStatement)
		) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('Using boundVariables'
				. ' in Extbase\'s custom statement without using preparedStatement is'
				. ' deprecated since TYPO3 6.2 and will be removed in two versions.');
		}
		$this->statement = $statement;
		$this->boundVariables = $boundVariables;
	}

	/**
	 * Gets the statement.
	 *
	 * @return string|\TYPO3\CMS\Core\Database\PreparedStatement the statement; non-null
	 */
	public function getStatement() {
		return $this->statement;
	}

	/**
	 * Gets the bound variables
	 *
	 * @return array $boundVariables
	 */
	public function getBoundVariables() {
		return $this->boundVariables;
	}

	/**
	 * Fills an array with the names of all bound variables in the constraints
	 *
	 * @param array &$boundVariables
	 * @return void
	 */
	public function collectBoundVariableNames(&$boundVariables) {
	}
}
