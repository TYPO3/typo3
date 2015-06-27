<?php
namespace TYPO3\CMS\Rtehtmlarea\Extension;

/*
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi;
use TYPO3\CMS\Rtehtmlarea\RteHtmlAreaBase;

/**
 * SelectFont extension for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
class SelectFont extends RteHtmlAreaApi {

	/**
	 * The name of the plugin registered by the extension
	 *
	 * @var string
	 */
	protected $pluginName = 'SelectFont';

	/**
	 * The comma-separated list of button names that the registered plugin is adding to the htmlArea RTE toolbar
	 *
	 * @var string
	 */
	protected $pluginButtons = 'fontstyle,fontsize';

	/**
	 * The name-converting array, converting the button names used in the RTE PageTSConfing to the button id's used by the JS scripts
	 *
	 * @var array
	 */
	protected $convertToolbarForHtmlAreaArray = array(
		'fontstyle' => 'FontName',
		'fontsize' => 'FontSize'
	);

	/**
	 * List of default fonts
	 *
	 * @var array
	 */
	protected $defaultFont = array(
		'fontstyle' => array(
			'Arial' => 'Arial,sans-serif',
			'Arial Black' => '\'Arial Black\',sans-serif',
			'Verdana' => 'Verdana,Arial,sans-serif',
			'Times New Roman' => '\'Times New Roman\',Times,serif',
			'Garamond' => 'Garamond',
			'Lucida Handwriting' => '\'Lucida Handwriting\'',
			'Courier' => 'Courier',
			'Webdings' => 'Webdings',
			'Wingdings' => 'Wingdings'
		),
		'fontsize' => array(
			'Extra small' => '8px',
			'Very small' => '9px',
			'Small' => '10px',
			'Medium' => '12px',
			'Large' => '16px',
			'Very large' => '24px',
			'Extra large' => '32px'
		)
	);

	/**
	 * RTE properties
	 *
	 * @var array
	 */
	protected $RTEProperties;

	/**
	 * Returns TRUE if the plugin is available and correctly initialized
	 *
	 * @param RteHtmlAreaBase $parentObject parent object
	 * @return bool TRUE if this plugin object should be made available in the current environment and is correctly initialized
	 */
	public function main($parentObject) {
		$enabled = parent::main($parentObject) && $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['allowStyleAttribute'];
		if ($this->htmlAreaRTE->is_FE()) {
			$this->RTEProperties = $this->htmlAreaRTE->RTEsetup;
		} else {
			$this->RTEProperties = $this->htmlAreaRTE->RTEsetup['properties'];
		}
		return $enabled;
	}

	/**
	 * Return JS configuration of the htmlArea plugins registered by the extension
	 *
	 * @param string $rteNumberPlaceholder A dummy string for JS arrays
	 * @return string JS configuration for registered plugins
	 */
	public function buildJavascriptConfiguration($rteNumberPlaceholder) {
		$registerRTEinJavascriptString = '';
		$pluginButtonsArray = GeneralUtility::trimExplode(',', $this->pluginButtons);
		// Process Page TSConfig configuration for each button
		foreach ($pluginButtonsArray as $buttonId) {
			if (in_array($buttonId, $this->toolbar)) {
				$registerRTEinJavascriptString .= $this->buildJSFontItemsConfig($rteNumberPlaceholder, $buttonId);
			}
		}
		return $registerRTEinJavascriptString;
	}

	/**
	 * Return Javascript configuration of font faces
	 *
	 * @param string $rteNumberPlaceholder A dummy string for JS arrays
	 * @param string $buttonId: button id
	 * @return string Javascript configuration of font faces
	 */
	protected function buildJSFontItemsConfig($rteNumberPlaceholder, $buttonId) {
		$configureRTEInJavascriptString = '';
		$hideItems = '';
		$addItems = array();
		// Getting removal and addition configuration
		if (is_array($this->thisConfig['buttons.']) && is_array($this->thisConfig['buttons.'][$buttonId . '.'])) {
			if ($this->thisConfig['buttons.'][$buttonId . '.']['removeItems']) {
				$hideItems = $this->thisConfig['buttons.'][$buttonId . '.']['removeItems'];
			}
			if ($this->thisConfig['buttons.'][$buttonId . '.']['addItems']) {
				$addItems = GeneralUtility::trimExplode(',', $this->htmlAreaRTE->cleanList($this->thisConfig['buttons.'][$buttonId . '.']['addItems']), TRUE);
			}
		}
		$languageService = $this->getLanguageService();
		// Initializing the items array
		$languageKey = $buttonId == 'fontstyle' ? 'Default font' : 'Default size';
		$items = array(
			'none' => array(
				$languageService->sL(
					'LLL:EXT:rtehtmlarea/Resources/Private/Language/Plugins/SelectFont/locallang.xlf:' . $languageKey
				),
				'none'
			),
		);
		// Inserting and localizing default items
		if ($hideItems != '*') {
			$index = 0;
			foreach ($this->defaultFont[$buttonId] as $name => $value) {
				if (!GeneralUtility::inList($hideItems, strval(($index + 1)))) {
					$label = $languageService->sL('LLL:EXT:rtehtmlarea/Resources/Private/Language/Plugins/SelectFont/locallang.xlf:' . $name) ?: $name;
					$items[$name] = array($label, $this->htmlAreaRTE->cleanList($value));
				}
				$index++;
			}
		}
		// Adding configured items
		if (is_array($this->RTEProperties[$buttonId == 'fontstyle' ? 'fonts.' : 'fontSizes.'])) {
			foreach ($this->RTEProperties[$buttonId == 'fontstyle' ? 'fonts.' : 'fontSizes.'] as $name => $conf) {
				$name = substr($name, 0, -1);
				if (in_array($name, $addItems)) {
					$label = $this->htmlAreaRTE->getPageConfigLabel($conf['name'], 0);
					$items[$name] = array($label, $this->htmlAreaRTE->cleanList($conf['value']));
				}
			}
		}
		// Seting default item
		if ($this->thisConfig['buttons.'][$buttonId . '.']['defaultItem'] && $items[$this->thisConfig['buttons.'][$buttonId . '.']['defaultItem']]) {
			$items['none'] = array($items[$this->thisConfig['buttons.'][$buttonId . '.']['defaultItem']][0], 'none');
			unset($items[$this->thisConfig['buttons.'][$buttonId . '.']['defaultItem']]);
		}
		// Setting the JS list of options
		$itemsJSArray = array();
		foreach ($items as $name => $option) {
			$itemsJSArray[] = array('text' => $option[0], 'value' => $option[1]);
		}
		if ($this->htmlAreaRTE->is_FE()) {
			$GLOBALS['TSFE']->csConvObj->convArray($itemsJSArray, $this->htmlAreaRTE->OutputCharset, 'utf-8');
		}
		$itemsJSArray = json_encode(array('options' => $itemsJSArray));
		// Adding to button JS configuration
		if (!is_array($this->thisConfig['buttons.']) || !is_array($this->thisConfig['buttons.'][($buttonId . '.')])) {
			$configureRTEInJavascriptString .= '
			RTEarea[' . $rteNumberPlaceholder . '].buttons.' . $buttonId . ' = new Object();';
		}
		$configureRTEInJavascriptString .= '
			RTEarea[' . $rteNumberPlaceholder . '].buttons.' . $buttonId . '.dataUrl = "' . ($this->htmlAreaRTE->is_FE() && $GLOBALS['TSFE']->absRefPrefix ? $GLOBALS['TSFE']->absRefPrefix : '') . $this->htmlAreaRTE->writeTemporaryFile($buttonId . '_' . $this->htmlAreaRTE->contentLanguageUid, 'js', $itemsJSArray) . '";';
		return $configureRTEInJavascriptString;
	}

}
