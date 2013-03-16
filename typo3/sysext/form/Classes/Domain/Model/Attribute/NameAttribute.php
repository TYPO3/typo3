<?php
namespace TYPO3\CMS\Form\Domain\Model\Attribute;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Patrick Broens (patrick@patrickbroens.nl)
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
 * Attribute 'name'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class NameAttribute extends \TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute {

	/**
	 * Addition to the name value
	 *
	 * @var string
	 */
	protected $addition;

	/**
	 * TRUE if value is expected without prefix
	 *
	 * @var boolean
	 */
	protected $returnValueWithoutPrefix = FALSE;

	/**
	 * Return the name attribute without the prefix
	 *
	 * @return string
	 */
	public function getValueWithoutPrefix() {
		$value = (string) $this->value;
		// Change spaces into hyphens
		$value = preg_replace('/\\s/', '-', $value);
		// Remove non-word characters
		$value = preg_replace('/[^a-zA-Z0-9_\\-]+/', '', $value);
		if (empty($value)) {
			$value = $this->elementId;
		}
		return $value;
	}

	/**
	 * Gets the attribute 'name'.
	 * Used with all elements
	 * Case Insensitive
	 *
	 * This attribute names the element so that it may be referred to
	 * from style sheets or scripts.
	 *
	 * Note: This attribute has been included for backwards compatibility.
	 * Applications should use the id attribute to identify elements.
	 * This does not apply for form objects, only the form tag
	 *
	 * @return string Attribute value
	 */
	public function getValue() {
		$value = $this->getValueWithoutPrefix();
		if ($this->returnValueWithoutPrefix === FALSE) {
			$requestHandler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Form\\Request');
			$attribute = $requestHandler->getPrefix() . '[' . $value . ']' . $this->addition;
		} else {
			$attribute = $value;
		}
		return $attribute;
	}

	/**
	 * Sets an additional string which will be added to the name
	 * This is necessarry in some cases like a multiple select box
	 *
	 * @param string $addition The additional string
	 * @return \TYPO3\CMS\Form\Domain\Model\Attribute\NameAttribute
	 */
	public function setAddition($addition) {
		$this->addition = (string) $addition;
		return $this;
	}

	/**
	 * TRUE if element is not allowed to use a prefix
	 * This is the case with the <form> tag
	 *
	 * @param boolean $parameter
	 * @return void
	 */
	public function setReturnValueWithoutPrefix($parameter) {
		$this->returnValueWithoutPrefix = (bool) $parameter;
	}

}

?>