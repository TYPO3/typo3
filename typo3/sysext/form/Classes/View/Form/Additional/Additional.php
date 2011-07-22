<?php
declare(encoding = 'utf-8');

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008 Patrick Broens (patrick@patrickbroens.nl)
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
 * @package TYPO3
 * @subpackage form
 */
class tx_form_view_form_additional extends tx_form_view_form_element_abstract {

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
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function __construct($model) {
		$this->model = $model;
	}

	/**
	 * Get the additional value
	 *
	 * @return string The value of the additional
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getAdditionalValue() {
		$type = preg_replace('/.*_([^_]*)$/', "$1", get_class($this), 1);
		return htmlspecialchars($this->model->getAdditionalValue($type), ENT_QUOTES);
	}
}
?>