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
 * View object for the checkboxgroup element
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class CheckboxGroupElementView extends \TYPO3\CMS\Form\View\Mail\Plain\Element\ContainerElementView {

	/**
	 * @return string
	 */
	public function render() {
		$content = '';
		if ($this->model->additionalIsSet('legend')) {
			$content = $this->model->getAdditionalValue('legend') . ': ' . LF;
		}
		$content .= $this->renderChildren($this->model->getElements(), $this->spaces + 4);
		return str_repeat(chr(32), $this->spaces) . $content;
	}

}
