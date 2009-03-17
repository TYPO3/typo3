<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * A persistence query interface
 *
 * @package TYPO3
 * @subpackage extmvc
 * @version $ID:$
 */
// TODO Do we want to have this class? - If not: remove.
interface QueryInterface {

	/**
	 * Executes the query against the backend and returns the result
	 *
	 * @return array The query result, an array of objects
	 */
	public function execute();

	/**
	 * The constraint used to limit the result set
	 *
	 * @param mixed $constraint Some constraint, depending on the backend
	 * @return TX_EXTMVC_Persistence_QueryInterface
	 */
	public function matching($constraint);

	/**
	 * Returns an equals criterion used for matching objects against a query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return TX_EXTMVC_Persistence_OperatorInterface
	 */
	public function equals($property, $operand);

	/**
	 * Returns a like criterion used for matching objects against a query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return TX_EXTMVC_Persistence_OperatorInterface
	 */
	public function like($property, $operand);

	/**
	 * Returns a less than criterion used for matching objects against a query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return TX_EXTMVC_Persistence_OperatorInterface
	 */
	public function lessThan($property, $operand);

	/**
	 * Returns a less or equal than criterion used for matching objects against a query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return TX_EXTMVC_Persistence_OperatorInterface
	 */
	public function lessThanOrEqual($property, $operand);

	/**
	 * Returns a greater than criterion used for matching objects against a query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return TX_EXTMVC_Persistence_OperatorInterface
	 */
	public function greaterThan($property, $operand);

	/**
	 * Returns a greater than or equal criterion used for matching objects against a query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return TX_EXTMVC_Persistence_OperatorInterface
	 */
	public function greaterThanOrEqual($property, $operand);

}
?>