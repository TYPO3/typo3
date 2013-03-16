<?php
namespace TYPO3\CMS\Core\Resource;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Andreas Wolf <andreas.wolf@ikt-werk.de>
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
 */
class FileCollectionRepository extends \TYPO3\CMS\Core\Collection\RecordCollectionRepository {

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
	 * @todo the parent function throws already an exception if not found
	 * @param integer $uid The uid to be looked up
	 * @return NULL|Collection\AbstractFileCollection
	 * @throws \RuntimeException
	 */
	public function findByUid($uid) {
		$object = parent::findByUid($uid);
		if ($object === NULL) {
			throw new \RuntimeException('Could not find row with uid "' . $uid . '" in table "' . $this->table . '"', 1314354065);
		}
		return $object;
	}

	/**
	 * Creates a record collection domain object.
	 *
	 * @param array $record Database record to be reconsituted
	 *
	 * @return Collection\AbstractFileCollection
	 */
	protected function createDomainObject(array $record) {
		return $this->getFileFactory()->createCollectionObject($record);
	}

	/**
	 * Gets the file factory.
	 *
	 * @return ResourceFactory
	 */
	protected function getFileFactory() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ResourceFactory');
	}

}


?>