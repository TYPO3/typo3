<?php
namespace TYPO3\CMS\Backend\Template\Components\Buttons;

/*
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

use TYPO3\CMS\Backend\Template\Components\AbstractControl;
use TYPO3\CMS\Core\Imaging\Icon;

/**
 * AbstractButton
 */
class AbstractButton extends AbstractControl implements ButtonInterface {

	/**
	 * Icon object
	 *
	 * @var Icon
	 */
	protected $icon;

	/**
	 * ButtonType
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Get icon
	 *
	 * @return Icon
	 */
	public function getIcon() {
		return $this->icon;
	}

	/**
	 * Get type
	 *
	 * @return string
	 */
	public function getType() {
		return get_class($this);
	}

	/**
	 * Set icon
	 *
	 * @param Icon $icon Icon object for the button
	 *
	 * @return $this
	 */
	public function setIcon(Icon $icon) {
		$this->icon = $icon;
		return $this;
	}

	/**
	 * Implementation from ButtonInterface
	 * This object is an abstract, so no implementation is necessary
	 *
	 * @return bool
	 */
	public function isValid() {
		return FALSE;
	}

	/**
	 * Implementation from ButtonInterface
	 * This object is an abstract, so no implementation is necessary
	 *
	 * @return string
	 */
	public function __toString() {
		return '';
	}

	/**
	 * Implementation from ButtonInterface
	 * This object is an abstract, so no implementation is necessary
	 *
	 * @return string
	 */
	public function render() {
		return '';
	}


}