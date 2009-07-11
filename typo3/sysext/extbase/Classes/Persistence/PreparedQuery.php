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
 * A prepared query. A new prepared query is created by calling
 * QueryManager->createPreparedQuery.
 *
 * @package Extbase
 * @subpackage Persistence
 * @version $Id: PreparedQuery.php 2120 2009-04-02 10:06:31Z k-fish $
 * @scope prototype
 */
// SK: I think this can be removed for now.
class Tx_Extbase_Persistence_PreparedQuery extends Tx_Extbase_Persistence_Query implements Tx_Extbase_Persistence_PreparedQueryInterface {

	/**
	 * @var array
	 */
	protected $boundVariables = array();

	/**
	 * Binds the given value to the variable named $varName.
	 *
	 * @param string $varName name of variable in query
	 * @param Tx_Extbase_Persistence_ValueInterface $value value to bind
	 * @return void
	 * @throws InvalidArgumentException if $varName is not a valid variable in this query.
	 * @throws RepositoryException if an error occurs.
	 */
	public function bindValue($varName, Tx_Extbase_Persistence_ValueInterface $value) {
		if (array_key_exists($varName, $this->boundVariables) === FALSE) {
			throw new InvalidArgumentException('Invalid variable name "' . $varName . '" given to bindValue.', 1217241834);
		}
		$this->boundVariables[$varName] = $value->getString();
	}

	/**
	 * Returns the values of all bound variables.
	 *
	 * @return array()
	 */
	public function getBoundVariableValues() {
		return $this->boundVariables;
	}
}

?>