<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 * A lazy result list that is returned by Query::execute()
 *
 * @api
 */
class QueryResult implements \TYPO3\CMS\Extbase\Persistence\QueryResultInterface {

	/**
	 * This field is only needed to make debugging easier:
	 * If you call current() on a class that implements Iterator, PHP will return the first field of the object
	 * instead of calling the current() method of the interface.
	 * We use this unusual behavior of PHP to return the warning below in this case.
	 *
	 * @var string
	 * @deprecated since Extbase 1.3.0; will be removed in Extbase 6.1
	 */
	private $warning = 'You should never see this warning. If you do, you probably used PHP array functions like current() on the TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QueryResult. To retrieve the first result, you can use the getFirst() method.';

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
	 */
	protected $dataMapper;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 */
	protected $query;

	/**
	 * @var array
	 * @transient
	 */
	protected $queryResult;

	/**
	 * Constructor
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\QueryInterface $query
	 */
	public function __construct(\TYPO3\CMS\Extbase\Persistence\QueryInterface $query) {
		$this->query = $query;
	}

	/**
	 * Injects the DataMapper to map records to objects
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper
	 * @return void
	 */
	public function injectDataMapper(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper) {
		$this->dataMapper = $dataMapper;
	}

	/**
	 * Injects the persistence manager
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager
	 * @return void
	 */
	public function injectPersistenceManager(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * Loads the objects this QueryResult is supposed to hold
	 *
	 * @return void
	 */
	protected function initialize() {
		if (!is_array($this->queryResult)) {
			$this->queryResult = $this->dataMapper->map($this->query->getType(), $this->persistenceManager->getObjectDataByQuery($this->query));
		}
	}

	/**
	 * Returns a clone of the query object
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 * @api
	 */
	public function getQuery() {
		return clone $this->query;
	}

	/**
	 * Returns the first object in the result set
	 *
	 * @return object
	 * @api
	 */
	public function getFirst() {
		if (is_array($this->queryResult)) {
			$queryResult = $this->queryResult;
			reset($queryResult);
		} else {
			$query = $this->getQuery();
			$query->setLimit(1);
			$queryResult = $this->dataMapper->map($query->getType(), $this->persistenceManager->getObjectDataByQuery($query));
		}
		$firstResult = current($queryResult);
		if ($firstResult === FALSE) {
			$firstResult = NULL;
		}
		return $firstResult;
	}

	/**
	 * Returns the number of objects in the result
	 *
	 * @return integer The number of matching objects
	 * @api
	 */
	public function count() {
		if (is_array($this->queryResult)) {
			return count($this->queryResult);
		} else {
			return $this->persistenceManager->getObjectCountByQuery($this->query);
		}
	}

	/**
	 * Returns an array with the objects in the result set
	 *
	 * @return array
	 * @api
	 */
	public function toArray() {
		$this->initialize();
		return iterator_to_array($this);
	}

	/**
	 * This method is needed to implement the ArrayAccess interface,
	 * but it isn't very useful as the offset has to be an integer
	 *
	 * @param mixed $offset
	 * @return boolean
	 * @see ArrayAccess::offsetExists()
	 */
	public function offsetExists($offset) {
		$this->initialize();
		return isset($this->queryResult[$offset]);
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 * @see ArrayAccess::offsetGet()
	 */
	public function offsetGet($offset) {
		$this->initialize();
		return isset($this->queryResult[$offset]) ? $this->queryResult[$offset] : NULL;
	}

	/**
	 * This method has no effect on the persisted objects but only on the result set
	 *
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 * @see ArrayAccess::offsetSet()
	 */
	public function offsetSet($offset, $value) {
		$this->initialize();
		$this->queryResult[$offset] = $value;
	}

	/**
	 * This method has no effect on the persisted objects but only on the result set
	 *
	 * @param mixed $offset
	 * @return void
	 * @see ArrayAccess::offsetUnset()
	 */
	public function offsetUnset($offset) {
		$this->initialize();
		unset($this->queryResult[$offset]);
	}

	/**
	 * @return mixed
	 * @see Iterator::current()
	 */
	public function current() {
		$this->initialize();
		return current($this->queryResult);
	}

	/**
	 * @return mixed
	 * @see Iterator::key()
	 */
	public function key() {
		$this->initialize();
		return key($this->queryResult);
	}

	/**
	 * @return void
	 * @see Iterator::next()
	 */
	public function next() {
		$this->initialize();
		next($this->queryResult);
	}

	/**
	 * @return void
	 * @see Iterator::rewind()
	 */
	public function rewind() {
		$this->initialize();
		reset($this->queryResult);
	}

	/**
	 * @return boolean
	 * @see Iterator::valid()
	 */
	public function valid() {
		$this->initialize();
		return current($this->queryResult) !== FALSE;
	}

	/**
	 * @return void
	 */
	public function __wakeup() {
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->persistenceManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\PersistenceManagerInterface');
		$this->dataMapper = $objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapper');
	}

	/**
	 * @return array
	 */
	public function __sleep() {
		return array('query');
	}
}

?>