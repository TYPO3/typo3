<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture;

/***************************************************************
 *  Copyright notice
 *
 *  All rights reserved
 *
 *  This class is a backport of the corresponding class of TYPO3 Flow.
 *  All credits go to the TYPO3 Flow team.
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * ArrayAccess class for the Reflection tests
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ArrayAccessClass implements \ArrayAccess {

	protected $array = array();

	/**
	 * @param array $array
	 */
	public function __construct(array $array) {
		$this->array = $array;
	}

	/**
	 * @param mixed $offset
	 * @return boolean
	 */
	public function offsetExists($offset) {
		return array_key_exists($offset, $this->array);
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset) {
		return $this->array[$offset];
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value) {
		$this->array[$offset] = $value;
	}

	/**
	 * @param mixed $offset
	 */
	public function offsetUnset($offset) {
		unset($this->array[$offset]);
	}
}

?>