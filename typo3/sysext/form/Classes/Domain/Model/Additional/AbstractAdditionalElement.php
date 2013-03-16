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

?>