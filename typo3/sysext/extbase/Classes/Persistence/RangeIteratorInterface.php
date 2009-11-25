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
 * Extends Iterator with the skip, getSize and getPosition methods. The base
 * interface of all type-specific iterators in the JCR and its sub packages.
 *
 * @package Extbase
 * @subpackage Persistence
 * @version $Id: RangeIteratorInterface.php 1729 2009-11-25 21:37:20Z stucki $
 */
interface Tx_Extbase_Persistence_RangeIteratorInterface extends Tx_Extbase_Persistence_IteratorInterface {

	/**
	 * Skip a number of elements in the iterator.
	 *
	 * @param integer $skipNum the non-negative number of elements to skip
	 * @throws OutOfBoundsException if skipped past the last element in the iterator.
	 */
	public function skip($skipNum);

	/**
	 * Returns the total number of of items available through this iterator.
	 *
	 * For example, for some node $n, $n->getNodes()->getSize() returns the
	 * number of child nodes of $n visible through the current Session.
	 *
	 * In some implementations precise information about the number of elements may
	 * not be available. In such cases this method must return -1. API clients will
	 * then be able to use RangeIterator->getNumberRemaining() to get an
	 * estimate on the number of elements.
	 *
	 * @return integer
	 */
	public function getSize();

	/**
	 * Returns the current position within the iterator. The number
	 * returned is the 0-based index of the next element in the iterator,
	 * i.e. the one that will be returned on the subsequent next() call.
	 *
	 * Note that this method does not check if there is a next element,
	 * i.e. an empty iterator will always return 0.
	 *
	 * @return integer
	 */
	public function getPosition();

}
?>