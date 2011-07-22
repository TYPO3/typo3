<?php
declare(encoding = 'utf-8');

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
class tx_form_view_mail_plain_element_container extends tx_form_view_mail_plain_element {

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function __construct($model, $spaces) {
		parent::__construct($model, $spaces);
	}

	protected function renderChildren(array $children, $spaces = 0) {
		foreach ($children as $child) {
			$content .= $this->renderChild($child, $spaces);
		}

		return $content;
	}

	protected function renderChild($modelChild, $spaces) {
		$content = '';
		$modelChildClass = get_class($modelChild);
		$class = preg_replace('/.*_([^_]*)$/', "$1", $modelChildClass, 1);

		$className = 'tx_form_view_mail_plain_element_' . $class;

		if (class_exists($className)) {
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