<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Contract for a repository
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @author Robert Lemke <robert@typo3.org>
 */
interface TX_EXTMVC_Persistence_RepositoryInterface {

	/**
	 * Sets the class name of the aggregare root
	 *
	 * @param string $aggregateRootClassName 
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function setAggregateRootClassName($aggregateRootClassName);

	/**
	 * Returns the class name of the aggregare root
	 *
	 * @return string The class name of the aggregate root
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function getAggregateRootClassName();

	/**
	 * Adds an object to this repository.
	 *
	 * @param object $object The object to add
	 * @return void
	 */
	public function add($object);

	/**
	 * Removes an object from this repository.
	 *
	 * @param object $object The object to remove
	 * @return void
	 */
	public function remove($object);

}
?>