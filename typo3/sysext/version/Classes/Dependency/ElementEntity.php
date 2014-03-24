<?php
namespace TYPO3\CMS\Version\Dependency;

/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2013 Oliver Hader <oliver@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Object to hold information on a dependent database element in abstract.
 */
class ElementEntity {

	const REFERENCES_ChildOf = 'childOf';
	const REFERENCES_ParentOf = 'parentOf';
	const EVENT_Construct = 'TYPO3\\CMS\\Version\\Dependency\\ElementEntity::construct';
	const EVENT_CreateChildReference = 'TYPO3\\CMS\\Version\\Dependency\\ElementEntity::createChildReference';
	const EVENT_CreateParentReference = 'TYPO3\\CMS\\Version\\Dependency\\ElementEntity::createParentReference';
	const RESPONSE_Skip = 'TYPO3\\CMS\\Version\\Dependency\\ElementEntity->skip';

	/**
	 * @var bool
	 */
	protected $invalid = FALSE;

	/**
	 * @var string
	 */
	protected $table;

	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var array
	 */
	protected $data;

	/**
	 * @var array
	 */
	protected $record;

	/**
	 * @var \TYPO3\CMS\Version\Dependency\DependencyResolver
	 */
	protected $dependency;

	/**
	 * @var array
	 */
	protected $children;

	/**
	 * @var array
	 */
	protected $parents;

	/**
	 * @var boolean
	 */
	protected $traversingParents = FALSE;

	/**
	 * @var \TYPO3\CMS\Version\Dependency\ElementEntity
	 */
	protected $outerMostParent;

	/**
	 * @var array
	 */
	protected $nestedChildren;

	/**
	 * Creates this object.
	 *
	 * @param string $table
	 * @param integer $id
	 * @param array $data (optional)
	 * @param \TYPO3\CMS\Version\Dependency\DependencyResolver $dependency
	 */
	public function __construct($table, $id, array $data = array(), \TYPO3\CMS\Version\Dependency\DependencyResolver $dependency) {
		$this->table = $table;
		$this->id = (int)$id;
		$this->data = $data;
		$this->dependency = $dependency;
		$this->dependency->executeEventCallback(self::EVENT_Construct, $this);
	}

	/**
	 * @param bool $invalid
	 */
	public function setInvalid($invalid) {
		$this->invalid = (bool)$invalid;
	}

	/**
	 * @return bool
	 */
	public function isInvalid() {
		return $this->invalid;
	}

	/**
	 * Gets the table.
	 *
	 * @return string
	 */
	public function getTable() {
		return $this->table;
	}

	/**
	 * Gets the id.
	 *
	 * @return integer
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Sets the id.
	 *
	 * @param int $id
	 */
	public function setId($id) {
		$this->id = (int)$id;
	}

	/**
	 * Gets the data.
	 *
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Gets a value for a particular key from the data.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function getDataValue($key) {
		$result = NULL;
		if ($this->hasDataValue($key)) {
			$result = $this->data[$key];
		}
		return $result;
	}

	/**
	 * Sets a value for a particular key in the data.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function setDataValue($key, $value) {
		$this->data[$key] = $value;
	}

	/**
	 * Determines whether a particular key holds data.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function hasDataValue($key) {
		return isset($this->data[$key]);
	}

	/**
	 * Converts this object for string representation.
	 *
	 * @return string
	 */
	public function __toString() {
		return self::getIdentifier($this->table, $this->id);
	}

	/**
	 * Gets the parent dependency object.
	 *
	 * @return \TYPO3\CMS\Version\Dependency\DependencyResolver
	 */
	public function getDependency() {
		return $this->dependency;
	}

	/**
	 * Gets all child references.
	 *
	 * @return array|ReferenceEntity[]
	 */
	public function getChildren() {
		if (!isset($this->children)) {
			$this->children = array();
			$where = 'tablename=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->table, 'sys_refindex') . ' AND recuid='
				. $this->id . ' AND workspace=' . $this->dependency->getWorkspace();
			$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'sys_refindex', $where, '', 'sorting');
			if (is_array($rows)) {
				foreach ($rows as $row) {
					$arguments = array('table' => $row['ref_table'], 'id' => $row['ref_uid'], 'field' => $row['field'], 'scope' => self::REFERENCES_ChildOf);
					$callbackResponse = $this->dependency->executeEventCallback(self::EVENT_CreateChildReference, $this, $arguments);
					if ($callbackResponse !== self::RESPONSE_Skip) {
						$this->children[] = $this->getDependency()->getFactory()->getReferencedElement(
							$row['ref_table'],
							$row['ref_uid'],
							$row['field'],
							array(),
							$this->getDependency()
						);
					}
				}
			}
		}
		return $this->children;
	}

	/**
	 * Gets all parent references.
	 *
	 * @return array|ReferenceEntity[]
	 */
	public function getParents() {
		if (!isset($this->parents)) {
			$this->parents = array();
			$where = 'ref_table=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->table, 'sys_refindex')
				. ' AND deleted=0 AND ref_uid=' . $this->id . ' AND workspace=' . $this->dependency->getWorkspace();
			$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'sys_refindex', $where, '', 'sorting');
			if (is_array($rows)) {
				foreach ($rows as $row) {
					$arguments = array('table' => $row['tablename'], 'id' => $row['recuid'], 'field' => $row['field'], 'scope' => self::REFERENCES_ParentOf);
					$callbackResponse = $this->dependency->executeEventCallback(self::EVENT_CreateParentReference, $this, $arguments);
					if ($callbackResponse !== self::RESPONSE_Skip) {
						$this->parents[] = $this->getDependency()->getFactory()->getReferencedElement(
							$row['tablename'],
							$row['recuid'],
							$row['field'],
							array(),
							$this->getDependency()
						);
					}
				}
			}
		}
		return $this->parents;
	}

	/**
	 * Determines whether there are child or parent references.
	 *
	 * @return boolean
	 */
	public function hasReferences() {
		return count($this->getChildren()) > 0 || count($this->getParents()) > 0;
	}

	/**
	 * Gets the outermost parent element.
	 *
	 * @return ElementEntity
	 */
	public function getOuterMostParent() {
		if (!isset($this->outerMostParent)) {
			$parents = $this->getParents();
			if (count($parents) === 0) {
				$this->outerMostParent = $this;
			} else {
				$this->outerMostParent = FALSE;
				/** @var $parent \TYPO3\CMS\Version\Dependency\ReferenceEntity */
				foreach ($parents as $parent) {
					$outerMostParent = $parent->getElement()->getOuterMostParent();
					if ($outerMostParent instanceof \TYPO3\CMS\Version\Dependency\ElementEntity) {
						$this->outerMostParent = $outerMostParent;
						break;
					} elseif ($outerMostParent === FALSE) {
						break;
					}
				}
			}
		}
		return $this->outerMostParent;
	}

	/**
	 * Gets nested children accumulated.
	 *
	 * @return array|ReferenceEntity[]
	 */
	public function getNestedChildren() {
		if (!isset($this->nestedChildren)) {
			$this->nestedChildren = array();
			$children = $this->getChildren();
			/** @var $child \TYPO3\CMS\Version\Dependency\ReferenceEntity */
			foreach ($children as $child) {
				$this->nestedChildren = array_merge($this->nestedChildren, array($child->getElement()->__toString() => $child->getElement()), $child->getElement()->getNestedChildren());
			}
		}
		return $this->nestedChildren;
	}

	/**
	 * Converts the object for string representation.
	 *
	 * @param string $table
	 * @param integer $id
	 * @return string
	 */
	static public function getIdentifier($table, $id) {
		return $table . ':' . $id;
	}

	/**
	 * Gets the database record of this element.
	 *
	 * @return array
	 */
	public function getRecord() {
		if (empty($this->record['uid']) || (int)$this->record['uid'] !== $this->id) {
			$this->record = array();
			$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,pid,t3ver_wsid,t3ver_state,t3ver_oid', $this->getTable(), 'uid=' . $this->getId());
			if (is_array($rows)) {
				$this->record = $rows[0];
			}
		}
		return $this->record;
	}

}
