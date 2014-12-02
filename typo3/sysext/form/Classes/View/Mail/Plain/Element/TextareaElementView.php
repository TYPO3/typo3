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
 * View object for the textarea element
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class TextareaElementView extends \TYPO3\CMS\Form\View\Mail\Plain\Element\AbstractElementView {

	/**
	 * @return string
	 */
	public function render() {
		$content = $this->getLabel() . ': ' . LF . str_repeat(chr(32), ($this->spaces + 4)) . $this->getData();
		return str_repeat(chr(32), $this->spaces) . $content;
	}

	/**
	 * @return mixed
	 */
	protected function getLabel() {
		$label = '';
		if ($this->model->additionalIsSet('label')) {
			$label = $this->model->getAdditionalValue('label');
		} else {
			$label = $this->model->getAttributeValue('name');
		}
		return $label;
	}

	/**
	 * @return string
	 */
	protected function getData() {
		$value = str_replace(LF, LF . str_repeat(chr(32), ($this->spaces + 4)), $this->model->getData());
		return $value;
	}

}
