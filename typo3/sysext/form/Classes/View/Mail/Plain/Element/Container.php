<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Patrick Broens (patrick@patrickbroens.nl)
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
 * Main view layer for plain mail container content.
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_View_Mail_Plain_Element_Container extends tx_form_View_Mail_Plain_Element_Abstract {
	/**
	 * @param array $children
	 * @param integer $spaces
	 * @return string
	 */
	protected function renderChildren(array $children, $spaces = 0) {
		$content = '';

		/** @var $child tx_form_Domain_Model_Element_Abstract */
		foreach ($children as $child) {
			$content .= $this->renderChild($child, $spaces);
		}

		return $content;
	}

	/**
	 * @param tx_form_Domain_Model_Element_Abstract $modelChild
	 * @param integer $spaces
	 * @return string
	 */
	protected function renderChild(tx_form_Domain_Model_Element_Abstract $modelChild, $spaces) {
		$content = '';

		$class = tx_form_Common::getInstance()->getLastPartOfClassName($modelChild);
		$className = 'tx_form_View_Mail_Plain_Element_' . ucfirst($class);

		if (class_exists($className)) {
			/** @var $childElement tx_form_View_Mail_Plain_Element_Abstract */
			$childElement = t3lib_div::makeInstance($className, $modelChild, $spaces);
			$elementContent = $childElement->render();

			if ($elementContent != '') {
				$content = $childElement->render() . chr(10);
			}
		}

		return $content;

	}
}
?>