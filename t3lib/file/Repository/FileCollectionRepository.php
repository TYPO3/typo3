<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2012 Andreas Wolf <andreas.wolf@ikt-werk.de>
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
 * Repository for accessing the collections stored in the database
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @author Ingmar Schlecht <ingmar@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_file_Repository_FileCollectionRepository extends t3lib_collection_RecordCollectionRepository {
	/**
	 * @var string
	 */
	protected $table = 'sys_file_collection';

	/**
	 * @var string
	 */
	protected $typeField = 'type';

	/**
	 * Finds a record collection by uid.
	 *
	 * @param integer $uid The uid to be looked up
	 * @return NULL|t3lib_file_Collection_AbstractFileCollection
	 */
	public function findByUid($uid) {
		$object = parent::findByUid($uid);

		if ($object === NULL) {
			throw new RuntimeException(
				'Could not find row with uid ' . $uid . ' in table ' . $this->table,
				1314354065
			);
		}

		return $object;
	}

	/**
	 * Finds record collection by type.
	 *
	 * @param string $type The type to be looked up
	 * @return NULL|t3lib_file_Collection_AbstractFileCollection[]
	 */
	public function findByType($type) {
		return parent::findByType($type);
	}

	/**
	 * Creates a record collection domain object.
	 *
	 * @param $record Database record to be reconsituted
	 * @return t3lib_file_Collection_AbstractFileCollection
	 */
	protected function createDomainObject(array $record) {
		return $this->getFileFactory()->createCollectionObject($record);
	}

	/**
	 * Gets the file factory.
	 *
	 * @return t3lib_file_Factory
	 */
	protected function getFileFactory() {
		return t3lib_div::makeInstance('t3lib_file_Factory');
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/file/Repository/FileCollectionRepository.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/file/Repository/FileCollectionRepository.php']);
}

?>