<?php
namespace TYPO3\CMS\Form\Domain\Model\Additional;

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
 * Abstract for additional
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
abstract class AbstractAdditionalElement {

	/**
	 * Additional value
	 *
	 * @var string
	 */
	protected $value;

	/**
	 * Additional type
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Additional layout
	 *
	 * @var string
	 */
	protected $layout;

	/**
	 * The content object
	 *
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	protected $localCobj;

	/**
	 * Constructor
	 *
	 * @param string $type Type of the object
	 * @param mixed $value Value of the object
	 */
	public function __construct($type, $value) {
		/** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer localCobj */
		$this->localCobj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
		$this->value = $value;
		$this->type = $type;
	}

	/**
	 * Get the layout string
	 *
	 * @return string XML string
	 */
	public function getLayout() {
		return $this->layout;
	}

	/**
	 * Set the layout
	 *
	 * @param string $layout XML string
	 * @return void
	 */
	public function setLayout($layout) {
		$this->layout = (string) $layout;
	}

	/**
	 * Returns the value of the object
	 *
	 * @return string
	 */
	abstract public function getValue();

}
