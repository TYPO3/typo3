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
 * View object for the textline element
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class TextlineElementView extends \TYPO3\CMS\Form\View\Mail\Plain\Element\AbstractElementView {

	/**
	 * @return string
	 */
	public function render() {
		$content = $this->getLabel() . ': ' . $this->getValue();
		return str_repeat(chr(32), $this->spaces) . $content;
	}

	/**
	 * @return mixed
	 */
	protected function getLabel() {
		if ($this->model->additionalIsSet('label')) {
			$label = $this->model->getAdditionalValue('label');
		} else {
			$label = $this->model->getAttributeValue('name');
		}
		return $label;
	}

	/**
	 * @return mixed
	 */
	protected function getValue() {
		$value = $this->model->getAttributeValue('value');
		return $value;
	}

}
