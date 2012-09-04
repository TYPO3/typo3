<?php
namespace TYPO3\CMS\Core\Resource\Property;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Helmut Hummel <helmut.hummel@typo3.org>
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
 * Factory class for FAL objects.
 *
 *
 *
 * @author Helmut Hummel <helmut.hummel@typo3.org>
 */
/**
 *
 */
class PropertyBag extends AbstractPropertyBag {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var array
	 */
	protected $properties;

	/**
	 * @var boolean
	 */
	protected $isDirty;

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param string $name
	 * @param array $properties
	 * @throws \InvalidArgumentException
	 */
	public function __construct($name, array $properties) {
		if (!is_string($name)) {
			throw new \InvalidArgumentException();
		}
		$this->name = $name;
		$this->properties = $properties;
	}

	/**
	 * @return array
	 */
	public function getPropertyNames() {
		return array_keys($this->properties);
	}

	/**
	 * @param mixed $index
	 * @return bool
	 */
	public function offsetExists($index) {
		return array_key_exists($index, $this->properties);
	}

	/**
	 * @param mixed $index
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	public function offsetGet($index) {
		if (!$this->offsetExists($index)) {
			throw new \InvalidArgumentException('Property "' . $index . '" does not exist.', 1341571841);
		}

		return $this->properties[$index];
	}

	/**
	 * @param mixed $index
	 * @param mixed $newval
	 */
	public function offsetSet($index, $newval) {
		$this->isDirty = TRUE;
		parent::offsetSet($index, $newval);
	}

	/**
	 * @param mixed $index
	 */
	public function offsetUnset($index) {
		$this->isDirty = TRUE;
		parent::offsetUnset($index);
	}

	/**
	 * @return boolean
	 */
	public function getIsDirty() {
		return $this->isDirty;
	}

	/**
	 * @return int
	 */
	public function count() {
		return count($this->properties);
	}

	/**
	 * @param string $serialized
	 */
	public function unserialize($serialized) {
		parent::unserialize($serialized);
	}

	/**
	 * @return string
	 */
	public function serialize() {
		return serialize($this->properties);
	}

	/**
	 *
	 */
	public function getIterator() {
		parent::getIterator();
	}


}

