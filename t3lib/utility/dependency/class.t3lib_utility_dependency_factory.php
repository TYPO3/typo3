<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2011 Oliver Hader <oliver@typo3.org>
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
class t3lib_utility_Dependency_Factory {
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
	 * @param t3lib_utility_Dependency $dependency
	 * @return t3lib_utility_Dependency_Element
	 */
	public function getElement($table, $id, array $data = array(), t3lib_utility_Dependency $dependency) {
		$elementName = $table . ':' . $id;
		if (!isset($this->elements[$elementName])) {
			$this->elements[$elementName] = t3lib_div::makeInstance(
				't3lib_utility_Dependency_Element',
				$table, $id, $data, $dependency
			);
		}
		return $this->elements[$elementName];
	}

	/**
	 * Gets and registers a new reference.
	 *
	 * @param t3lib_utility_Dependency_Element $element
	 * @param string $field
	 * @return t3lib_utility_Dependency_Reference
	 */
	public function getReference(t3lib_utility_Dependency_Element $element, $field) {
		$referenceName = $element->__toString() . '.' . $field;
		if (!isset($this->references[$referenceName][$field])) {
			$this->references[$referenceName][$field] = t3lib_div::makeInstance(
				't3lib_utility_Dependency_Reference',
				$element, $field
			);
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
	 * @param t3lib_utility_Dependency $dependency
	 * @return t3lib_utility_Dependency_Reference
	 * @see getElement
	 * @see getReference
	 */
	public function getReferencedElement($table, $id, $field, array $data = array(), t3lib_utility_Dependency $dependency) {
		return $this->getReference(
			$this->getElement($table, $id, $data, $dependency),
			$field
		);
	}
}
