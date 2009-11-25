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
 * Allows easy iteration through a list of Rows with nextRow as well as a skip
 * method inherited from RangeIterator.
 *
 * @package Extbase
 * @subpackage Persistence
 * @version $Id: RowIterator.php 1729 2009-11-25 21:37:20Z stucki $
 * @scope prototype
 */
class Tx_Extbase_Persistence_RowIterator extends Tx_Extbase_Persistence_RangeIterator implements Tx_Extbase_Persistence_RowIteratorInterface {

	/**
	 * Returns the next Row in the iteration.
	 *
	 * @return Tx_Extbase_Persistence_RowInterface
	 * @throws OutOfBoundsException if the iterator contains no more elements.
	 */
	public function nextRow() {
		return $this->next();
	}

}

?>