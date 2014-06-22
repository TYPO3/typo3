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
 * Content model object
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class ContentElement extends \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement {

	/**
	 * @var string
	 */
	protected $elementType = self::ELEMENT_TYPE_CONTENT;

	/**
	 * @var string
	 */
	protected $objectName;

	/**
	 * @var array
	 */
	protected $objectConfiguration;

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
	 * Set the content for the element
	 *
	 * @param string $objectName The name of the object
	 * @param array $objectConfiguration The configuration of the object
	 * @return void
	 */
	public function setData($objectName, array $objectConfiguration) {
		$this->objectName = $objectName;
		$this->objectConfiguration = $objectConfiguration;
	}

	/**
	 * Return the value data of the content object
	 * Calls \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->cObjGetSingle
	 * which renders configuration into html string
	 *
	 * @return string
	 */
	public function getData() {
		$data = $this->localCobj->cObjGetSingle($this->objectName, $this->objectConfiguration);
		return $data;
	}

}
