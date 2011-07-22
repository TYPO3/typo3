<?php
declare(encoding = 'utf-8');

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008 Patrick Broens <patrick@patrickbroens.nl>
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
 * @package TYPO3
 * @subpackage form
 */
class tx_form_system_localization implements t3lib_Singleton {

	/**
	 * File reference to the local language file
	 *
	 * @var string
	 */
	protected $localLanguageFile;

	/**
	 * Default language key to use
	 *
	 * @var string
	 */
	protected $localLanguageKey = 'default';

	/**
	 * Alternative language key
	 *
	 * @var string
	 */
	protected $alternativeLocalLanguageKey = '';

	/**
	 * Language labels in the right language
	 *
	 * @var array
	 */
	protected $localLanguageLabels = array();

	/**
	 * Constructor
	 *
	 * @param $localLanguageFile string File reference to the local language file
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function __construct($localLanguageFile) {
		$this->localLanguageFile = (string) $localLanguageFile;

		if (
			isset($GLOBALS['TSFE']->config['config']['language']) &&
			!empty($GLOBALS['TSFE']->config['config']['language'])
		) {
			$this->localLanguageKey = $GLOBALS['TSFE']->config['config']['language'];

			if (
				isset($GLOBALS['TSFE']->config['config']['language_alt']) &&
				!empty($GLOBALS['TSFE']->config['config']['language_alt'])
			) {
				$this->alternativeLocalLanguageKey = $GLOBALS['TSFE']->config['config']['language_alt'];
			}
		}

		$this->loadLocalLanguageLabels();
	}

	/**
	 * Load all labels from a language XML file
	 *
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function loadLocalLanguageLabels() {
		$this->localLanguageLabels = t3lib_div::readLLfile(
			$this->localLanguageFile,
			$this->localLanguageKey,
			$GLOBALS['TSFE']->renderCharset
		);

		if ($this->alternativeLocalLanguageKey) {
			$tempLocalLangueLabels = t3lib_div::readLLfile(
				$this->localLanguageFile,
				$this->alternativeLocalLanguageKey
			);

			$this->localLanguageLabels = array_merge(
				is_array($this->localLanguageLabels) ?
					$this->localLanguageLabels :
					array(),
				$tempLocalLangueLabels
			);
		}
	}

	/**
	 * Get a label from local language
	 * If not available, use the alternative label
	 * Option to put label through htmlspecialchars
	 *
	 * @param $labelKey string Key to look for
	 * @param $alternativeLabel string Alternative if label is not found
	 * @param $htmlSpecialChars boolean If TRUE, use htmlspecialchars
	 * @return string
	 */
	public function getLocalLanguageLabel(
		$labelKey,
		$alternativeLabel = '',
		$htmlSpecialChars = FALSE
	) {
		if (isset($this->localLanguageLabels[$this->localLanguageKey][$labelKey])) {
			$sentence = $this->localLanguageLabels[$this->localLanguageKey][$labelKey];
		} elseif (
			$this->alternativeLocalLanguageKey &&
			isset($this->localLanguageLabels[$this->alternativeLocalLanguageKey][$labelKey])
		) {
			$sentence = $this->localLanguageLabels[$this->alternativeLocalLanguageKey][$labelKey];
		} elseif (isset($this->localLanguageLabels['default'][$labelKey])) {
			$sentence = $this->localLanguageLabels['default'][$labelKey];
		} else {
			$sentence = $alternativeLabel;
		}

		$output = $sentence;

		if ($htmlSpecialChars) {
			$output = htmlspecialchars($output);
		}

		return $output;
	}
}
?>