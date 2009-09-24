<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
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
 * Configuration source based on FlexForm settings
 *
 * @package Extbase
 * @subpackage Configuration\Source
 * @version $ID:$
 */
class Tx_Extbase_Configuration_Source_FlexFormSource implements Tx_Extbase_Configuration_Source_SourceInterface {

	/**
	 * XML FlexForm content
	 *
	 * @var string
	 **/
	protected $flexFormContent;

	/**
	 * Sets the flex form content
	 *
	 * @param string $flexFormContent Flexform content
	 * @return void
	 */
	public function setFlexFormContent($flexFormContent) {
		$this->flexFormContent = $flexFormContent;
	}

	/**
	 * Loads the specified FlexForm configuration  and returns its content in a
	 * configuration container. If the file does not exist or could not be loaded,
	 * the empty configuration container is returned.
	 *
	 * @param string $extensionName The extension name
	 * @return array
	 */
	public function load($extensionName) {
		$settings = array();
		if (!empty($this->flexFormContent)) {
			$settings = $this->convertFlexFormContentToArray($this->flexFormContent);
		}

		return $settings;
	}

	/**
	 * Parses the FlexForm content recursivly and converts it to an array
	 * The resulting array will be one-dimensional. So make sure not to use the same key multiple times
	 * or it will be overwritten.
	 * Note: multi-language FlexForms are not supported yet
	 *
	 * @param string $flexFormContent FlexForm xml string
	 * @return array
	 */
	protected function convertFlexFormContentToArray($flexFormContent) {
		$settings = array();
		$languagePointer = 'lDEF';
		$valuePointer = 'vDEF';

		$flexFormArray = t3lib_div::xml2array($flexFormContent);
		$flexFormArray = isset($flexFormArray['data']) ? $flexFormArray['data'] : array();
		foreach(array_values($flexFormArray) as $languages) {
			if (!is_array($languages[$languagePointer])) {
				continue;
			}
			foreach($languages[$languagePointer] as $valueKey => $valueDefinition) {
				$settings[$valueKey] = $valueDefinition[$valuePointer];
			}
		}
		return $settings;
	}

}
?>