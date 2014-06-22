<?php
namespace TYPO3\CMS\Form\View\Mail\Plain;

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
 * Main view layer for plain mail content.
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class PlainView extends \TYPO3\CMS\Form\View\Mail\Plain\Element\ContainerElementView {

	/**
	 * Constructor
	 *
	 * @param \TYPO3\CMS\Form\Domain\Model\Form $model
	 * @param integer $spaces
	 */
	public function __construct(\TYPO3\CMS\Form\Domain\Model\Form $model, $spaces = 0) {
		parent::__construct($model, $spaces);
	}

	/**
	 * @return string Plain content containing the submitted values
	 */
	public function render() {
		$content = $this->renderChildren($this->model->getElements());
		return $content;
	}

}
