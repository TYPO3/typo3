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
 * View object for the hidden element
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class HiddenElementView extends \TYPO3\CMS\Form\View\Mail\Plain\Element\AbstractElementView {

	/**
	 * Constructor
	 *
	 * @param \TYPO3\CMS\Form\Domain\Model\Element\HiddenElement $model Model for this element
	 * @param integer $spaces
	 */
	public function __construct(\TYPO3\CMS\Form\Domain\Model\Element\HiddenElement $model, $spaces) {
		parent::__construct($model, $spaces);
	}

	/**
	 * @return string
	 */
	public function render() {
		$content = $this->getLabel() . ': ' . $this->getValue();
		return str_repeat(chr(32), $this->spaces) . $content;
	}

	/**
	 * @return string
	 */
	protected function getLabel() {
		$label = $this->model->getName();
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
