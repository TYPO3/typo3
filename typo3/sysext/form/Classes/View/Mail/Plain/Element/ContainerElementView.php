<?php
namespace TYPO3\CMS\Form\View\Mail\Plain\Element;

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
