<?php
namespace TYPO3\CMS\Form\View\Mail\Plain\Element;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Patrick Broens (patrick@patrickbroens.nl)
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
 */
class ContainerElementView extends \TYPO3\CMS\Form\View\Mail\Plain\Element\AbstractElementView {

	/**
	 * @param array $children
	 * @param integer $spaces
	 * @return string
	 */
	protected function renderChildren(array $children, $spaces = 0) {
		$content = '';
		/** @var $child \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement */
		foreach ($children as $child) {
			$content .= $this->renderChild($child, $spaces);
		}
		return $content;
	}

	/**
	 * @param \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement $modelChild
	 * @param integer $spaces
	 * @return string
	 */
	protected function renderChild(\TYPO3\CMS\Form\Domain\Model\Element\AbstractElement $modelChild, $spaces) {
		$content = '';
		$class = \TYPO3\CMS\Form\Utility\FormUtility::getInstance()->getLastPartOfClassName($modelChild);
		$className = 'TYPO3\\CMS\\Form\\View\\Mail\\Plain\\Element\\' . ucfirst($class) . 'ElementView';
		if (class_exists($className)) {
			/** @var $childElement \TYPO3\CMS\Form\View\Mail\Plain\Element\AbstractElementView */
			$childElement = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($className, $modelChild, $spaces);
			$elementContent = $childElement->render();
			if ($elementContent != '') {
				$content = $childElement->render() . chr(10);
			}
		}
		return $content;
	}

}

?>