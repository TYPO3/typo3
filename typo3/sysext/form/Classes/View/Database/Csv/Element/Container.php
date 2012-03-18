<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Franz Geiger <mail@fx-g.de>
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
 * Main view layer for plain data container content.
 *
 * @author Franz Geiger <mail@fx-g.de>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_View_Database_Csv_Element_Container extends tx_form_View_Database_Csv_Element_Abstract {

	const QUOTE_SIGN = '"';
	/**
	 * @param array $children
	 * @param string $delimiter
	 * @return string
	 */
	protected function renderChildren(array $children, $delimiter) {
		$headerLine = array();
		$contentLine = array();

		/** @var $child tx_form_Domain_Model_Element_Abstract */
		foreach ($children as $child) {
			$headerLine[] = $this->renderChildHeader($child);
			$contentLine[] = $this->renderChildContent($child);
		}
	
		$content = implode($delimiter, $headerLine) .
				chr(10) . 
				implode($delimiter, $contentLine);

		return $content;
	}

	/**
	 * @param tx_form_Domain_Model_Element_Abstract $modelChild
	 * @return string
	 */
	protected function renderChild(tx_form_Domain_Model_Element_Abstract $modelChild) {
		$elementContent = '';
		if (in_array($modelChild->getAttributeValue('name');

		$class = tx_form_Common::getInstance()->getLastPartOfClassName($modelChild);
		$className = 'tx_form_View_Database_Csv_Element_' . ucfirst($class);

		if (class_exists($className)) {
			/** @var $childElement tx_form_View_Database_Csv_Element_Abstract */
			$childElement = t3lib_div::makeInstance($className, $modelChild);
			$elementContent = $childElement->renderContent();
		}

		return QUOTE_SIGN . $content . QUOTE_SIGN;

	}
}
?>
