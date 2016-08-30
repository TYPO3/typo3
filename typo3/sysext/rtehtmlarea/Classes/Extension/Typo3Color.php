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
 * TYPO3 Color plugin for htmlArea RTE
 */
class Typo3Color extends RteHtmlAreaApi
{
    /**
     * The name of the plugin registered by the extension
     *
     * @var string
     */
    protected $pluginName = 'TYPO3Color';

    /**
     * The comma-separated list of button names that the registered plugin is adding to the htmlArea RTE toolbar
     *
     * @var string
     */
    protected $pluginButtons = 'textcolor,bgcolor';

    /**
     * The name-converting array, converting the button names used in the RTE PageTSConfing to the button id's used by the JS scripts
     *
     * @var array
     */
    protected $convertToolbarForHtmlAreaArray = [
        'textcolor' => 'ForeColor',
        'bgcolor' => 'HiliteColor'
    ];

    /**
     * Returns TRUE if the plugin is available and correctly initialized
     *
     * @param array $configuration Configuration array given from calling object down to the single plugins
     * @return bool TRUE if this plugin object should be made available in the current environment and is correctly initialized
     */
    public function main(array $configuration)
    {
        return parent::main($configuration)
            && $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['allowStyleAttribute'];
    }

    /**
     * Return Javascript configuration of colors
     *
     * @return string Javascript configuration of colors
     */
    public function buildJavascriptConfiguration()
    {
        $jsArray = [];
        $jsArray[] = 'RTEarea[editornumber].disableColorPicker = ' . (trim($this->configuration['thisConfig']['disableColorPicker']) ? 'true' : 'false') . ';';
        // Building the array of configured colors
        $HTMLAreaColorName = [];
        if (is_array($this->configuration['RTEsetup']['properties']['colors.'])) {
            foreach ($this->configuration['RTEsetup']['properties']['colors.'] as $colorName => $conf) {
                $colorName = substr($colorName, 0, -1);
                $colorLabel = $this->getPageConfigLabel($conf['name']);
                $HTMLAreaColorName[$colorName] = [$colorLabel, strtoupper(substr($conf['value'], 1, 6))];
            }
        }
        // Setting the list of colors if specified in the RTE config
        if ($this->configuration['thisConfig']['colors']) {
            $HTMLAreaColors = GeneralUtility::trimExplode(',', $this->cleanList($this->configuration['thisConfig']['colors']));
            $HTMLAreaJSColors = [];
            foreach ($HTMLAreaColors as $colorName) {
                if ($HTMLAreaColorName[$colorName]) {
                    $HTMLAreaJSColors[] = $HTMLAreaColorName[$colorName];
                }
            }
            $jsArray[] = 'RTEarea[editornumber].colors = ' . json_encode($HTMLAreaJSColors) . ';';
        }
        return implode(LF, $jsArray);
    }
}
