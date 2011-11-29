<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Steffen Ritter <typo3steffen-ritter.net>
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
 * Interface for collection classes which es enabled to be modified
 *
 * @author Steffen Ritter <typo3steffen-ritter.net>
 * @package TYPO3
 * @subpackage t3lib
 */
interface t3lib_collection_Editable {

	/**
	 * Adds on entry to the collection
	 *
	 * @abstract
	 * @param mixed $data
	 * @return void
	 */
	public function add($data);

	/**
	 * Adds a set of entries to the collection
	 *
	 * @abstract
	 * @param t3lib_collection_Collection $other
	 * @return void
	 */
	public function addAll(t3lib_collection_Collection $other);

	/**
	 * Remove the given entry from collection
	 *
	 * Note: not the given "index"
	 *
	 * @abstract
	 * @param mixed $data
	 * @return void
	 */
	public function remove($data);

	/**
	 * Removes all entries from the collection
	 *
	 * collection will be empty afterwards
	 *
	 * @abstract
	 * @return void
	 */
	public function removeAll();
}

?>