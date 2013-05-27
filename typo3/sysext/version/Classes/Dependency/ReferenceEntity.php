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
 * Object to hold reference information of a database field and one accordant element.
 */
class ReferenceEntity {

	/**
	 * @var \TYPO3\CMS\Version\Dependency\ElementEntity
	 */
	protected $element;

	/**
	 * @var string
	 */
	protected $field;

	/**
	 * Creates this object.
	 *
	 * @param \TYPO3\CMS\Version\Dependency\ElementEntity $element
	 * @param string $field
	 */
	public function __construct(\TYPO3\CMS\Version\Dependency\ElementEntity $element, $field) {
		$this->element = $element;
		$this->field = $field;
	}

	/**
	 * Gets the elements.
	 *
	 * @return \TYPO3\CMS\Version\Dependency\ElementEntity
	 */
	public function getElement() {
		return $this->element;
	}

	/**
	 * Gets the field.
	 *
	 * @return string
	 */
	public function getField() {
		return $this->field;
	}

	/**
	 * Converts this object for string representation.
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->element . '.' . $this->field;
	}

}


?>