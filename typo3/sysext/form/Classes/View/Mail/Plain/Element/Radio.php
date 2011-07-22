<?php
declare(encoding = 'utf-8');

/***************************************************************
*  Copyright notice
*
*  (c) 2011 Patrick Broens (patrick@patrickbroens.nl)
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
 * View object for the radio element
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_view_mail_plain_element_radio extends tx_form_view_mail_plain_element {

	/**
	 * Constructor
	 *
	 * @param tx_form_domain_model_element_radio $model Model for this element
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function __construct(tx_form_domain_model_element_radio $model, $spaces) {
		parent::__construct($model, $spaces);
	}

	public function render() {
		$content = $this->getValue();

		if ($content) {
			return str_repeat(chr(32), $this->spaces) . $content;
		}
	}

	private function getValue() {
		$value = NULL;

		if (
			array_key_exists('checked', $this->model->getAllowedAttributes()) &&
			$this->model->hasAttribute('checked')
		) {
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
?>