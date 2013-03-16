<?php
namespace TYPO3\CMS\Form\Domain\Model\Additional;

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
 * Additional elements for FORM object
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class AdditionalAdditionalElement {

	/**
	 * Array with the additional objects of the element
	 *
	 * @var array
	 */
	protected $additional = array();

	/**
	 * Add an additional object to the additional array
	 *
	 * @param string $class Name of the additional
	 * @param mixed $value Typoscript configuration to construct value
	 * @param string $type Typoscript content object
	 * @return AdditionalAdditionalElement
	 */
	public function addAdditional($class, $type, $value) {
		$class = strtolower((string) $class);
		$className = 'TYPO3\\CMS\\Form\\Domain\\Model\\Additional\\' . ucfirst($class) . 'AdditionalElement';
		$this->additional[$class] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($className, $type, $value);
		return $this;
	}

	/**
	 * Get the additional array of the object
	 *
	 * @return array Additionals of the object
	 */
	public function getAdditional() {
		return $this->additional;
	}

	/**
	 * Get a specific additional object by using the key
	 *
	 * @param string $key Key of the additional
	 * @return \TYPO3\CMS\Form\Domain\Model\Additional\AbstractAdditionalElement The additional object
	 */
	public function getAdditionalObjectByKey($key) {
		return $this->additional[$key];
	}

	/**
	 * Check if an additional is set.
	 * Returns TRUE if set, FALSE if not set.
	 *
	 * @param string $key Name of the additional
	 * @return boolean
	 */
	public function additionalIsSet($key) {
		return isset($this->additional[$key]);
	}

	/**
	 * Set the layout for an additional
	 *
	 * @param string $key Key for the additional
	 * @param string $layout XML string
	 * @return void
	 */
	public function setLayout($key, $layout) {
		$this->getAdditionalObjectByKey($key)->setLayout($layout);
	}

	/**
	 * Get a specific additional value by using the key
	 *
	 * @param string $key Key of the additional
	 * @return string The value of the additional
	 */
	public function getValue($key) {
		return $this->getAdditionalObjectByKey($key)->getValue();
	}

}

?>