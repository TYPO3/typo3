<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Christian Kuhn <lolli@schwarzbu.ch>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Extends the usual select view helper, but additionally translates
 * the select option labels
 *
 * Example:
 * <belog:form.translateLabelSelect property="number" options="{settings.selectableNumberOfLogEntries}" optionLabelPrefix="numbers"
 *
 * Will lookup number.200 (or whatever optionValue is given) in locallang database
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 * @package TYPO3
 * @subpackage belog
 */
class Tx_Belog_ViewHelpers_Form_TranslateLabelSelectViewHelper extends Tx_Fluid_ViewHelpers_Form_SelectViewHelper {

	/**
	 * Initialize arguments.
	 *
	 * @return void
	 * @api
	 */
	public function initializeArguments() {
		parent::initializeArguments();
		$this->registerArgument('optionLabelPrefix', 'string', 'Prefix for locallang lookup');
	}

	/**
	 * Render the option tags.
	 *
	 * Extend the default handling by iterating over calculated options array and
	 * try to translate the value
	 *
	 * @return array an associative array of options, key will be the value of the option tag
	 */
	protected function getOptions() {
		$options = parent::getOptions();

		foreach ($options as $value => $label) {
			if (strlen($label) > 0) {
				$labelKey = $this->hasArgument('optionLabelPrefix') ? $this->arguments['optionLabelPrefix'] . $label : $label;
				$translatedLabel = Tx_Extbase_Utility_Localization::translate(
					$labelKey,
					$this->controllerContext->getRequest()->getControllerExtensionName()
				);
				if (strlen($translatedLabel) > 0) {
					$options[$value] = $translatedLabel;
				}
			}
		}
		return $options;
	}
}
?>