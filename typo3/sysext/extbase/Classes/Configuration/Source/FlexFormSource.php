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
class Tx_Extbase_Configuration_Source_FlexFormSource implements Tx_Extbase_Configuration_SourceInterface {

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
			$this->readFlexformIntoConf($this->flexFormContent, $settings);
		}


		return $settings;
	}

	/**
	 * Parses the FlexForm content recursivly and adds it to the configuration
	 *
	 * @param $flexFormContent
	 * @param array $settings
	 * @param boolean $recursive
	 * @return void
	 */
	private function readFlexformIntoConf($flexFormContent, &$settings, $recursive = FALSE) {
		// TODO Do we need the $recursive argument here?
		if ($recursive === FALSE) {
			$flexFormContent = t3lib_div::xml2array($flexFormContent, 'T3');
		}

		if (is_array($flexFormContent)) {
			if (isset($flexFormContent['data']['sDEF']['lDEF'])) {
				$flexFormContent = $flexFormContent['data']['sDEF']['lDEF'];
			}

			foreach ($flexFormContent as $key => $value) {
				if (is_array($value['el']) && count($value['el']) > 0) {
					foreach ($value['el'] as $ekey => $element) {
						if (isset($element['vDEF'])) {
							$settings[$ekey] =  $element['vDEF'];
						} else {
							if(is_array($element)) {
								$this->readFlexformIntoConf($element, $settings[$key][key($element)][$ekey], TRUE);
							} else {
								$this->readFlexformIntoConf($element, $settings[$key][$ekey], TRUE);
							}
						}
					}
				} else {
					$this->readFlexformIntoConf($value['el'], $settings[$key], TRUE);
				}
				if ($value['vDEF']) {
					$settings[$key] = $value['vDEF'];
				}
			}
		}
	}


}
?>