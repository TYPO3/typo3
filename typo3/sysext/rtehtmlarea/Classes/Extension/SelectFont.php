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

/**
 * SelectFont extension for htmlArea RTE
 */
class SelectFont extends RteHtmlAreaApi
{
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
    protected $convertToolbarForHtmlAreaArray = [
        'fontstyle' => 'FontName',
        'fontsize' => 'FontSize'
    ];

    /**
     * List of default fonts
     *
     * @var array
     */
    protected $defaultFont = [
        'fontstyle' => [
            'Arial' => 'Arial,sans-serif',
            'Arial Black' => '\'Arial Black\',sans-serif',
            'Verdana' => 'Verdana,Arial,sans-serif',
            'Times New Roman' => '\'Times New Roman\',Times,serif',
            'Garamond' => 'Garamond',
            'Lucida Handwriting' => '\'Lucida Handwriting\'',
            'Courier' => 'Courier',
            'Webdings' => 'Webdings',
            'Wingdings' => 'Wingdings'
        ],
        'fontsize' => [
            'Extra small' => '8px',
            'Very small' => '9px',
            'Small' => '10px',
            'Medium' => '12px',
            'Large' => '16px',
            'Very large' => '24px',
            'Extra large' => '32px'
        ]
    ];

    /**
     * RTE properties
     *
     * @var array
     */
    protected $RTEProperties;

    /**
     * Returns TRUE if the plugin is available and correctly initialized
     *
     * @param array $configuration Configuration array given from calling object down to the single plugins
     * @return bool TRUE if this plugin object should be made available in the current environment and is correctly initialized
     */
    public function main(array $configuration)
    {
        $enabled = parent::main($configuration) && $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['allowStyleAttribute'];
        $this->RTEProperties = $this->configuration['RTEsetup']['properties'];
        return $enabled;
    }

    /**
     * Return JS configuration of the htmlArea plugins registered by the extension
     *
     * @return string JS configuration for registered plugins
     */
    public function buildJavascriptConfiguration()
    {
        $jsArray = [];
        $pluginButtonsArray = GeneralUtility::trimExplode(',', $this->pluginButtons);
        // Process Page TSConfig configuration for each button
        foreach ($pluginButtonsArray as $buttonId) {
            if (in_array($buttonId, $this->toolbar)) {
                $jsArray[] = $this->buildJSFontItemsConfig($buttonId);
            }
        }
        return implode(LF, $jsArray);
    }

    /**
     * Return Javascript configuration of font faces
     *
     * @param string $buttonId: button id
     * @return string Javascript configuration of font faces
     */
    protected function buildJSFontItemsConfig($buttonId)
    {
        $jsArray = [];
        $hideItems = '';
        $addItems = [];
        // Getting removal and addition configuration
        if (is_array($this->configuration['thisConfig']['buttons.']) && is_array($this->configuration['thisConfig']['buttons.'][$buttonId . '.'])) {
            if ($this->configuration['thisConfig']['buttons.'][$buttonId . '.']['removeItems']) {
                $hideItems = $this->configuration['thisConfig']['buttons.'][$buttonId . '.']['removeItems'];
            }
            if ($this->configuration['thisConfig']['buttons.'][$buttonId . '.']['addItems']) {
                $addItems = GeneralUtility::trimExplode(',', $this->cleanList($this->configuration['thisConfig']['buttons.'][$buttonId . '.']['addItems']), true);
            }
        }
        $languageService = $this->getLanguageService();
        // Initializing the items array
        $languageKey = $buttonId == 'fontstyle' ? 'Default font' : 'Default size';
        $items = [
            'none' => [
                $languageService->sL(
                    'LLL:EXT:rtehtmlarea/Resources/Private/Language/Plugins/SelectFont/locallang.xlf:' . $languageKey
                ),
                'none'
            ],
        ];
        // Inserting and localizing default items
        if ($hideItems != '*') {
            $index = 0;
            foreach ($this->defaultFont[$buttonId] as $name => $value) {
                if (!GeneralUtility::inList($hideItems, strval(($index + 1)))) {
                    $label = $languageService->sL('LLL:EXT:rtehtmlarea/Resources/Private/Language/Plugins/SelectFont/locallang.xlf:' . $name) ?: $name;
                    $items[$name] = [$label, $this->cleanList($value)];
                }
                $index++;
            }
        }
        // Adding configured items
        if (is_array($this->RTEProperties[$buttonId == 'fontstyle' ? 'fonts.' : 'fontSizes.'])) {
            foreach ($this->RTEProperties[$buttonId == 'fontstyle' ? 'fonts.' : 'fontSizes.'] as $name => $conf) {
                $name = substr($name, 0, -1);
                if (in_array($name, $addItems)) {
                    $label = $this->getPageConfigLabel($conf['name']);
                    $items[$name] = [$label, $this->cleanList($conf['value'])];
                }
            }
        }
        // Seting default item
        if ($this->configuration['thisConfig']['buttons.'][$buttonId . '.']['defaultItem'] && $items[$this->configuration['thisConfig']['buttons.'][$buttonId . '.']['defaultItem']]) {
            $items['none'] = [$items[$this->configuration['thisConfig']['buttons.'][$buttonId . '.']['defaultItem']][0], 'none'];
            unset($items[$this->configuration['thisConfig']['buttons.'][$buttonId . '.']['defaultItem']]);
        }
        // Setting the JS list of options
        $itemsJSArray = [];
        foreach ($items as $name => $option) {
            $itemsJSArray[] = ['text' => $option[0], 'value' => $option[1]];
        }
        $itemsJSArray = json_encode(['options' => $itemsJSArray]);
        // Adding to button JS configuration
        if (!is_array($this->configuration['thisConfig']['buttons.']) || !is_array($this->configuration['thisConfig']['buttons.'][$buttonId . '.'])) {
            $jsArray[] = 'RTEarea[editornumber].buttons.' . $buttonId . ' = new Object();';
        }
        $jsArray[] = 'RTEarea[editornumber].buttons.' . $buttonId . '.dataUrl = "' . $this->writeTemporaryFile($buttonId . '_' . $this->configuration['contentLanguageUid'], 'js', $itemsJSArray) . '";';
        return implode(LF, $jsArray);
    }
}
