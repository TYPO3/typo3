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
 * An Iterator interface
 *
 * The methods next(), hasNext() and remove() as in java.util.Iterator
 * append() is something we thought would be nice...
 *
 * @package Extbase
 * @subpackage Persistence
 * @version $Id: IteratorInterface.php 1729 2009-11-25 21:37:20Z stucki $
 */
interface Tx_Extbase_Persistence_IteratorInterface extends Iterator {

	/**
	 * Returns the next element. Commented as PHP dows not allow overriding methods from extended interfaces...
	 *
	 * @return mixed
	 * @throws OutOfBoundsException if no next element exists
	 */
	//public function next();

	/**
	 * Returns true if the iteration has more elements.
	 *
	 * This is an alias of valid().
	 *
	 * @return boolean
	 */
	public function hasNext();

	/**
	 * Removes from the underlying collection the last element returned by the iterator.
	 * This method can be called only once per call to next. The behavior of an iterator
	 * is unspecified if the underlying collection is modified while the iteration is in
	 * progress in any way other than by calling this method.
	 *
	 * @return void
	 * @throws IllegalStateException if the next method has not yet been called, or the remove method has already been called after the last call to the next method.
	 */
	public function remove();

	/**
	 * Append a new element to the iteration
	 *
	 * @param mixed $element
	 * @return void
	 */
	public function append($element);
}
?>