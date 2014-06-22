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
 * View object for the select element
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class SelectElementView extends \TYPO3\CMS\Form\View\Mail\Plain\Element\ContainerElementView {

	/**
	 * Constructor
	 *
	 * @param \TYPO3\CMS\Form\Domain\Model\Element\SelectElement $model Model for this element
	 * @param integer $spaces
	 */
	public function __construct(\TYPO3\CMS\Form\Domain\Model\Element\SelectElement $model, $spaces) {
		parent::__construct($model, $spaces);
	}

	/**
	 * @return string
	 */
	public function render() {
		$content = $this->getLabel() . ': ' . chr(10) . $this->getValues();
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
	protected function getValues() {
		$values = $this->renderChildren($this->model->getElements(), $this->spaces + 4);
		return $values;
	}

}
