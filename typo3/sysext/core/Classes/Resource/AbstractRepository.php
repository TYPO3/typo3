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
 * Abstract repository implementing the basic repository methods
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @author Ingmar Schlecht <ingmar@typo3.org>
 */
abstract class AbstractRepository implements \TYPO3\CMS\Extbase\Persistence\RepositoryInterface, \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var string
	 */
	protected $table = '';

	/**
	 * @var ResourceFactory
	 */
	protected $factory;

	/**
	 * @var string
	 */
	protected $typeField = '';

	/**
	 * @var string
	 */
	protected $type = '';

	/**
	 * Creates this object.
	 */
	public function __construct() {
		$this->factory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ResourceFactory');
	}

	/**
	 * Adds an object to this repository.
	 *
	 * @param object $object The object to add
	 * @return void
	 * @api
	 */
	public function add($object) {

	}

	/**
	 * Removes an object from this repository.
	 *
	 * @param object $object The object to remove
	 * @return void
	 * @api
	 */
	public function remove($object) {

	}

	/**
	 * Replaces an object by another.
	 *
	 * @param object $existingObject The existing object
	 * @param object $newObject The new object
	 * @return void
	 * @api
	 */
	public function replace($existingObject, $newObject) {

	}

	/**
	 * Replaces an existing object with the same identifier by the given object
	 *
	 * @param object $modifiedObject The modified object
	 * @api
	 */
	public function update($modifiedObject) {

	}

	/**
	 * Returns all objects of this repository add()ed but not yet persisted to
	 * the storage layer.
	 *
	 * @return array An array of objects
	 */
	public function getAddedObjects() {

	}

	/**
	 * Returns an array with objects remove()d from the repository that
	 * had been persisted to the storage layer before.
	 *
	 * @return array
	 */
	public function getRemovedObjects() {

	}

	/**
	 * Returns all objects of this repository.
	 *
	 * @return array An array of objects, empty if no objects found
	 * @api
	 */
	public function findAll() {
		$itemList = array();
		$whereClause = 'deleted = 0';
		if ($this->type != '') {
			$whereClause .= ' AND ' . $this->typeField . ' = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->type, $this->table);
		}
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $this->table, $whereClause);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$itemList[] = $this->createDomainObject($row);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return $itemList;
	}

	/**
	 * Creates an object managed by this repository.
	 *
	 * @abstract
	 * @param array $databaseRow
	 * @return object
	 */
	abstract protected function createDomainObject(array $databaseRow);

	/**
	 * Returns the total number objects of this repository.
	 *
	 * @return integer The object count
	 * @api
	 */
	public function countAll() {

	}

	/**
	 * Removes all objects of this repository as if remove() was called for
	 * all of them.
	 *
	 * @return void
	 * @api
	 */
	public function removeAll() {

	}

	/**
	 * Finds an object matching the given identifier.
	 *
	 * @param int $uid The identifier of the object to find
	 *
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return object The matching object
	 * @api
	 */
	public function findByUid($uid) {
		if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($uid)) {
			throw new \InvalidArgumentException('uid has to be integer.', 1316779798);
		}
		$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', $this->table, 'uid=' . intval($uid) . ' AND deleted=0');
		if (empty($row) || !is_array($row)) {
			throw new \RuntimeException('Could not find row with uid "' . $uid . '" in table ' . $this->table, 1314354065);
		}
		return $this->createDomainObject($row);
	}

	/**
	 * Sets the property names to order the result by per default.
	 * Expected like this:
	 * array(
	 * 'foo' => Tx_Extbase_Persistence_QueryInterface::ORDER_ASCENDING,
	 * 'bar' => Tx_Extbase_Persistence_QueryInterface::ORDER_DESCENDING
	 * )
	 *
	 * @param array $defaultOrderings The property names to order by
	 *
	 * @throws \BadMethodCallException
	 * @return void
	 * @api
	 */
	public function setDefaultOrderings(array $defaultOrderings) {
		throw new \BadMethodCallException('Repository does not support the setDefaultOrderings() method.', 1313185906);
	}

	/**
	 * Sets the default query settings to be used in this repository
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $defaultQuerySettings The query settings to be used by default
	 *
	 * @throws \BadMethodCallException
	 * @return void
	 * @api
	 */
	public function setDefaultQuerySettings(\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $defaultQuerySettings) {
		throw new \BadMethodCallException('Repository does not support the setDefaultQuerySettings() method.', 1313185907);
	}

	/**
	 * Returns a query for objects of this repository
	 *
	 * @throws \BadMethodCallException
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 * @api
	 */
	public function createQuery() {
		throw new \BadMethodCallException('Repository does not support the createQuery() method.', 1313185908);
	}

	/**
	 * Finds an object matching the given identifier.
	 *
	 * @param mixed $identifier The identifier of the object to find
	 * @return object The matching object if found, otherwise NULL
	 * @api
	 */
	public function findByIdentifier($identifier) {
		return $this->findByUid($identifier);
	}

	/**
	 * Magic call method for repository methods.
	 *
	 * @param string $method Name of the method
	 * @param array $arguments The arguments
	 * @return void
	 */
	public function __call($method, $arguments) {
		// deliberately empty
	}

	/**
	 * Returns the object type this repository is managing.
	 *
	 * @return string
	 * @api
	 */
	public function getEntityClassName() {
		return $this->objectType;
	}

}


?>
