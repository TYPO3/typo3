<?php
namespace TYPO3\CMS\Core\Collection;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Steffen Ritter <typo3steffen-ritter.net>
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
 * Interface for collection class being persistable
 *
 * Collections are containers-classes handling the storage
 * of data values (f.e. strings, records, relations) in a
 * common and generic way, while the class manages the storage
 * in an appropriate way itself
 *
 * @author Steffen Ritter <typo3steffen-ritter.net>
 */
interface PersistableCollectionInterface {
	/**
	 * Get the identifier of the collection
	 *
	 * For database stored collections, this will be a integer,
	 * session stored, registry stored or other collections might
	 * use a string as well
	 *
	 * @return integer|string
	 */
	public function getIdentifier();

	/**
	 * Sets the identifier of the collection
	 *
	 * @param integer|string $id
	 * @return void
	 */
	public function setIdentifier($id);

	/**
	 * Loads the collections with the given id from persistence
	 *
	 * For memory reasons, per default only f.e. title, database-table,
	 * identifier (what ever static data is defined) is loaded.
	 * Entries can be load on first access.
	 *
	 * @param integer|string $id
	 * @param boolean $fillItems Populates the entries directly on load, might be bad for memory on large collections
	 * @return \TYPO3\CMS\Core\Collection\CollectionInterface
	 */
	static public function load($id, $fillItems = FALSE);

	/**
	 * Persists current collection state to underlying storage
	 *
	 * @return void
	 */
	public function persist();

	/**
	 * Populates the content-entries of the storage
	 *
	 * Queries the underlying storage for entries of the collection
	 * and adds them to the collection data.
	 *
	 * If the content entries of the storage had not been loaded on creation
	 * ($fillItems = false) this function is to be used for loading the contents
	 * afterwards.
	 *
	 * @return void
	 */
	public function loadContents();

}

?>