<?php
namespace TYPO3\CMS\Form\Domain\Model\Attribute;

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
 * Abstract for attribute objects
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
abstract class AbstractAttribute {

	/**
	 * The value of the attribute
	 *
	 * @var array
	 */
	protected $value;

	/**
	 * Internal Id of the element
	 *
	 * @var integer
	 */
	protected $elementId;

	/**
	 * The content object
	 *
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	protected $localCobj;

	/**
	 * Constructor
	 *
	 * @param string $value Attribute value
	 * @return void
	 */
	public function __construct($value, $elementId) {
		/** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer localCobj */
		$this->localCobj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
		$this->setValue($value);
		$this->elementId = (int)$elementId;
	}

	/**
	 * Set the value
	 *
	 * @param string $value The value to set
	 * @return void
	 */
	public function setValue($value) {
		if (is_string($value) === FALSE) {
			$value = '';
		}
		$this->value = $value;
	}

	/**
	 * Gets the accordant attribute value.
	 *
	 * @return string
	 */
	abstract public function getValue();

}
