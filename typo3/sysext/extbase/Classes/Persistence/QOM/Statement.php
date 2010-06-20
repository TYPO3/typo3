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
 * A statement acting as a constraint.
 *
 * @package Extbase
 * @subpackage Persistence\QOM
 * @version $Id: Statement.php 1993 2010-03-09 21:44:20Z jocrau $
 * @scope prototype
 */
class Tx_Extbase_Persistence_QOM_Statement {

	/**
	 * constants determining the language of the query
	 */
	const TYPO3_SQL_MYSQL = 'TYPO3-SQL-MYSQL';

	/**
	 * @var string
	 */
	protected $statement;

	/**
	 * @var array
	 */
	protected $boundVariables = array();
	
	/**
	 * @var string
	 */
	protected $language;

	/**
	 * Constructs the Statement instance
	 *
	 * @param string $statement The statement
	 * @param array $boundVariables An array of variables to bind to the statement
	 * @param string $language The query language. One of the above constants.
	 */
	public function __construct($statement, array $boundVariables = array(), $language = self::TYPO3_SQL_MYSQL) {
		$this->statement = $statement;
		$this->boundVariables = $boundVariables;
		$this->language = $language;
	}

	/**
	 * Gets the statement.
	 *
	 * @return the statement; non-null
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
	 * Gets the language.
	 *
	 * @return string The language; one of self::
	 */
	public function getLanguage() {
		return $this->language;
	}

}

?>