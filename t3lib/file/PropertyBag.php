<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Andreas Wolf <andreas.wolf@typo3.org>
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
 * A bag of properties.
 */
class t3lib_file_PropertyBag extends ArrayObject {

	/**
	 * The name of this property bag.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The properties in this bag.
	 *
	 * @var array
	 */
	protected $properties = array();

	/**
	 * @var array
	 */
	protected $propertyDefinitions;

	public function __construct(array $propertyDefinitions) {
		foreach ($propertyDefinitions as $name => $propertyDefinition) {
			$this->properties[$name] = NULL;
			$this->propertyDefinitions[$name] = $propertyDefinition;
		}
	}

	/**
	 * @return array
	 */
	public function getPropertyNames() {
		return array_keys($this->properties);
	}

	public function offsetExists($index) {
		return array_key_exists($index, $this->properties);
	}

	public function offsetGet($index) {
		if (!$this->offsetExists($index)) {
			throw new InvalidArgumentException('Property "' . $index . '" does not exist.', 1341571841);
		}

		return $this->properties[$index];
	}

	public function offsetSet($index, $newval) {
		parent::offsetSet($index, $newval);
	}

	public function offsetUnset($index) {
		parent::offsetUnset($index);
	}

	public function count() {
		return count($this->properties);
	}

	public function unserialize($serialized) {
		parent::unserialize($serialized);
	}

	public function serialize() {
		return serialize($this->properties);
	}

	public function getIterator() {
		parent::getIterator();
	}

}
