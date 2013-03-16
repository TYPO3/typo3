<?php
namespace TYPO3\CMS\Form\Domain\Model\Element;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Oliver Hader <oliver.hader@typo3.org>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Hidden field model object
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
class AbstractPlainElement extends \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement {

	/**
	 * @var string
	 */
	protected $elementType = self::ELEMENT_TYPE_PLAIN;

	/**
	 * Allowed attributes for this object
	 *
	 * @var array
	 */
	protected $allowedAttributes = array();

	/**
	 * Mandatory attributes for this object
	 *
	 * @var array
	 */
	protected $mandatoryAttributes = array();

	/**
	 * @var array
	 */
	protected $properties = array();

	/**
	 * Sets the properties.
	 *
	 * @param array $properties
	 * @return void
	 */
	public function setProperties(array $properties) {
		$this->properties = $properties;
	}

	/**
	 * Gets the data.
	 *
	 * @return string
	 */
	public function getData() {
		return $this->getContent();
	}

	/**
	 * Gets the content.
	 *
	 * @return string
	 */
	protected function getContent() {
		$content = '';
		if (isset($this->properties['content'])) {
			$content = $this->properties['content'];
		}
		return $content;
	}

}

?>