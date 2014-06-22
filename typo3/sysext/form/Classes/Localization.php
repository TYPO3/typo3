<?php
namespace TYPO3\CMS\Form;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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
