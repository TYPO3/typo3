<?php
namespace TYPO3\CMS\Extbase\Persistence;

/*                                                                        *
 * This script belongs to the Extbase framework.                          *
 *                                                                        *
 * This class is a backport of the corresponding class of FLOW3.          *
 * All credits go to the v5 team.                                         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
/**
 * A lazy result list that is returned by Query::execute()
 *
 * @package Extbase
 * @subpackage Persistence
 */
interface QueryResultInterface extends \Countable, \Iterator, \ArrayAccess
{
	/**
	 * Returns a clone of the query object
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 * @api
	 */
	public function getQuery();

	/**
	 * Returns the first object in the result set
	 *
	 * @return object
	 * @api
	 */
	public function getFirst();

	/**
	 * Returns an array with the objects in the result set
	 *
	 * @return array
	 * @api
	 */
	public function toArray();

}

?>