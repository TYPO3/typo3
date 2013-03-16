<?php
namespace TYPO3\CMS\Form\View\Mail\Html\Additional;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Patrick Broens (patrick@patrickbroens.nl)
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

?>