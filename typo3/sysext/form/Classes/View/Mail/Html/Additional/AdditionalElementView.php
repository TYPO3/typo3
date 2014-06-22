<?php
namespace TYPO3\CMS\Form\View\Mail\Html\Additional;

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
 * Additional elements for FORM object
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class AdditionalElementView extends \TYPO3\CMS\Form\View\Mail\Html\Element\AbstractElementView {

	/**
	 * The model for the current object
	 *
	 * @var object
	 */
	protected $model;

	/**
	 * Constructor
	 *
	 * @param object $model The parent model
	 * @return void
	 */
	public function __construct($model) {
		$this->model = $model;
	}

	/**
	 * Get the additional value
	 *
	 * @return string The value of the additional
	 */
	public function getAdditionalValue() {
		return htmlspecialchars(
			$this->model->getAdditionalValue(\TYPO3\CMS\Form\Utility\FormUtility::getInstance()->getLastPartOfClassName($this, TRUE)),
			ENT_QUOTES
		);
	}

}
