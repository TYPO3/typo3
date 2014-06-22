<?php
namespace TYPO3\CMS\Form\Domain\Model\Element;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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
