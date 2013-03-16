<?php
namespace TYPO3\CMS\Form;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Patrick Broens <patrick@patrickbroens.nl>
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
 * Class to handle localizations
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class Localization {

	/**
	 * File reference to the local language file
	 *
	 * @var string
	 */
	protected $localLanguageFile;

	/**
	 * Constructor
	 *
	 * @param string $localLanguageFile File reference to the local language file
	 */
	public function __construct($localLanguageFile = 'LLL:EXT:form/Resources/Private/Language/locallang_controller.xlf') {
		$this->localLanguageFile = (string) $localLanguageFile;
	}

	/**
	 * Get a label from local language
	 *
	 * @param string $labelKey Key to look for
	 * @return string
	 */
	public function getLocalLanguageLabel($labelKey) {
		if (TYPO3_MODE === 'FE') {
			$output = $GLOBALS['TSFE']->sL($this->localLanguageFile . ':' . $labelKey);
		} else {
			$output = $GLOBALS['LANG']->sL($this->localLanguageFile . ':' . $labelKey);
		}
		return $output;
	}

}

?>