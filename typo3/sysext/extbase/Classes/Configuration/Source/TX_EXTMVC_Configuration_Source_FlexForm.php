<?php

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * Configuration source based on FlexForm settings
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
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

	// SK: Change Doc comment
	/**
	 * Loads the specified TypoScript configuration file and returns its content in a
	 * configuration container. If the file does not exist or could not be loaded,
	 * the empty configuration container is returned.
	 *
	 * @param string $extensionKey The extension key
	 * @return TX_EXTMVC_Configuration_Container
	 */
	 public function load($extensionKey) {
		$settings = array();
		// SK. I'd say this does not work in case $this->flexFormContent IS already an array. Can this happen?
		if (!is_array($this->flexFormContent) && $this->flexFormContent) {
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