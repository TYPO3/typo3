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
 * Textarea model object
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class TextareaElement extends \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement {

	/**
	 * Allowed attributes for this object
	 *
	 * @var array
	 */
	protected $allowedAttributes = array(
		'accesskey' => '',
		'class' => '',
		'cols' => '40',
		'dir' => '',
		'disabled' => '',
		'id' => '',
		'lang' => '',
		'name' => '',
		'readonly' => '',
		'rows' => '5',
		'style' => '',
		'tabindex' => '',
		'title' => ''
	);

	/**
	 * Mandatory attributes for this object
	 *
	 * @var array
	 */
	protected $mandatoryAttributes = array(
		'name',
		'id'
	);

	/**
	 * Returns the content of the textarea tag
	 * <textarea>content</textarea>
	 *
	 * @return string
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Check the request handler on input of this field,
	 * filter the submitted data and add this to the right
	 * datapart of the element
	 *
	 * @return \TYPO3\CMS\Form\Domain\Model\Element\TextareaElement
	 * @see \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement::checkFilterAndSetIncomingDataFromRequest()
	 */
	public function checkFilterAndSetIncomingDataFromRequest() {
		if ($this->requestHandler->has($this->getName())) {
			$value = $this->requestHandler->getByMethod($this->getName());
			$value = $this->filter->filter($value);
			$this->data = $value;
		}
		return $this;
	}

}
