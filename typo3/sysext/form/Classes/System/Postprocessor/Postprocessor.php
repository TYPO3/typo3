<?php
declare(encoding = 'utf-8');

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Patrick Broens <patrick@patrickbroens.nl>
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
 * The post processor
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_system_postprocessor {

	/**
	 * Constructor
	 *
	 * @param $form tx_form_domain_model_form Form domain model
	 * @param $typoscript array Post processor TypoScript settings
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function __construct(tx_form_domain_model_form $form, array $typoScript) {
		$this->form = $form;
		$this->typoScript = $typoScript;
	}

	/**
	 * The main method called by the controller
	 *
	 * Iterates over the configured post processors and calls them with their
	 * own settings
	 *
	 * @return string HTML messages from the called processors
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function process() {
		$html = '';

		if (is_array($this->typoScript)) {
			$keys = t3lib_TStemplate::sortedKeyList($this->typoScript);
			foreach ($keys as $key)	{
				$className = 'tx_form_system_postprocessor_' . strtolower($this->typoScript[$key]);
				if (intval($key) && !strstr($key, '.')) {
					if(isset($this->typoScript[$key . '.'])) {
						$processorArguments = $this->typoScript[$key . '.'];
					} else {
						$processorArguments = array();
					}
				}

				if (class_exists($className, TRUE)) {
					$processor = t3lib_div::makeInstance($className, $this->form, $processorArguments);
					$html .= $processor->process();
				}
			}
		}

		return $html;
	}
}
?>