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
 * View object for the checkbox element
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class CheckboxElementView extends \TYPO3\CMS\Form\View\Mail\Plain\Element\AbstractElementView {

	/**
	 * Constructor
	 *
	 * @param \TYPO3\CMS\Form\Domain\Model\Element\CheckboxElement $model Model for this element
	 * @param integer $spaces
	 */
	public function __construct(\TYPO3\CMS\Form\Domain\Model\Element\CheckboxElement $model, $spaces) {
		parent::__construct($model, $spaces);
	}

	/**
	 * @return string
	 */
	public function render() {
		$content = $this->getValue();
		if (empty($content) === FALSE) {
			$content = str_repeat(chr(32), $this->spaces) . $content;
		}
		return $content;
	}

	/**
	 * @return mixed
	 */
	protected function getValue() {
		$value = NULL;
		if (array_key_exists('checked', $this->model->getAllowedAttributes()) && $this->model->hasAttribute('checked')) {
			if ($this->model->additionalIsSet('label')) {
				$value = $this->model->getAdditionalValue('label');
			} else {
				$value = $this->model->getAttributeValue('value');
				if ($value === '') {
					$value = $this->model->getAttributeValue('name');
				}
			}
		}
		return $value;
	}

}
