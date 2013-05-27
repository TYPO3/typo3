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
 * A copy is found in the textfile GPL.txt and important notices to the license
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
 * Object to create and keep track of element or reference entities.
 */
class DependencyEntityFactory {

	/**
	 * @var array
	 */
	protected $elements = array();

	/**
	 * @var array
	 */
	protected $references = array();

	/**
	 * Gets and registers a new element.
	 *
	 * @param string $table
	 * @param integer $id
	 * @param array $data (optional)
	 * @param \TYPO3\CMS\Version\Dependency\DependencyResolver $dependency
	 * @return \TYPO3\CMS\Version\Dependency\ElementEntity
	 */
	public function getElement($table, $id, array $data = array(), \TYPO3\CMS\Version\Dependency\DependencyResolver $dependency) {
		$elementName = $table . ':' . $id;
		if (!isset($this->elements[$elementName])) {
			$this->elements[$elementName] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Version\\Dependency\\ElementEntity', $table, $id, $data, $dependency);
		}
		return $this->elements[$elementName];
	}

	/**
	 * Gets and registers a new reference.
	 *
	 * @param \TYPO3\CMS\Version\Dependency\ElementEntity $element
	 * @param string $field
	 * @return \TYPO3\CMS\Version\Dependency\ReferenceEntity
	 */
	public function getReference(\TYPO3\CMS\Version\Dependency\ElementEntity $element, $field) {
		$referenceName = $element->__toString() . '.' . $field;
		if (!isset($this->references[$referenceName][$field])) {
			$this->references[$referenceName][$field] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Version\\Dependency\\ReferenceEntity', $element, $field);
		}
		return $this->references[$referenceName][$field];
	}

	/**
	 * Gets and registers a new reference.
	 *
	 * @param string $table
	 * @param integer $id
	 * @param string $field
	 * @param array $data (optional
	 * @param \TYPO3\CMS\Version\Dependency\DependencyResolver $dependency
	 * @return \TYPO3\CMS\Version\Dependency\ReferenceEntity
	 * @see getElement
	 * @see getReference
	 */
	public function getReferencedElement($table, $id, $field, array $data = array(), \TYPO3\CMS\Version\Dependency\DependencyResolver $dependency) {
		return $this->getReference($this->getElement($table, $id, $data, $dependency), $field);
	}

}


?>