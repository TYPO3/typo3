<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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
 * Configuration source based on FlexForm settings
 * 
 * @package TYPO3
 * @subpackage extmvc
 * @version $ID:$
 */
class TX_EXTMVC_Configuration_Source_FlexForm implements TX_EXTMVC_Configuration_SourceInterface {

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
	 * @param string $extensionKey The extension key
	 * @return TX_EXTMVC_Configuration_Container
	 */
	 public function load($extensionKey) {
		$settings = array();
		if (is_array($this->flexFormContent)) {
			$flexFormArray = $this->flexFormContent;
		} elseif (!empty($this->flexFormContent)) {
			$flexFormArray = t3lib_div::xml2array($this->flexFormContent);
		}
		$sheetArray = $flexFormArray['data']['sDEF']['lDEF'];
		if (is_array($sheetArray))	{
			foreach($sheetArray as $key => $value) {
				$settings[$key] = $value['vDEF'];
			}
		}
		return $settings;
	}

}
?>