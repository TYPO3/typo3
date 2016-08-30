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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi;

/**
 * TYPO3 Image plugin for htmlArea RTE
 */
class Typo3Image extends RteHtmlAreaApi
{
    /**
     * The name of the plugin registered by the extension
     *
     * @var string
     */
    protected $pluginName = 'TYPO3Image';

    /**
     * The comma-separated list of button names that the registered plugin is adding to the htmlArea RTE toolbar
     *
     * @var string
     */
    protected $pluginButtons = 'image';

    /**
     * The name-converting array, converting the button names used in the RTE PageTSConfing to the button id's used by the JS scripts
     *
     * @var array
     */
    protected $convertToolbarForHtmlAreaArray = [
        'image' => 'InsertImage'
    ];

    /**
     * Returns TRUE if the plugin is available and correctly initialized
     *
     * @param array $configuration Configuration array given from calling object down to the single plugins
     * @return bool TRUE if this plugin object should be made available in the current environment and is correctly initialized
     */
    public function main(array $configuration)
    {
        // Check if this should be enabled based on extension configuration and Page TSConfig
        // The 'Minimal' and 'Typical' default configurations include Page TSConfig that removes images on the way to the database
        return parent::main($configuration)
            && !(
                $this->configuration['thisConfig']['proc.']['entryHTMLparser_db.']['tags.']['img.']['allowedAttribs'] == '0'
                && $this->configuration['thisConfig']['proc.']['entryHTMLparser_db.']['tags.']['img.']['rmTagIfNoAttrib'] == '1'
            )
            && !$this->configuration['thisConfig']['buttons.']['image.']['TYPO3Browser.']['disabled'];
    }

    /**
     * Return JS configuration of the htmlArea plugins registered by the extension
     *
     * @return string JS configuration for registered plugins, in this case, JS configuration of block elements
     */
    public function buildJavascriptConfiguration()
    {
        $jsArray = [];
        $button = 'image';
        if (in_array($button, $this->toolbar)) {
            if (!is_array($this->configuration['thisConfig']['buttons.']) || !is_array($this->configuration['thisConfig']['buttons.'][$button . '.'])) {
                $jsArray[] = 'RTEarea[editornumber]["buttons"]["' . $button . '"] = new Object();';
            }
            $jsArray[] = 'RTEarea[editornumber].buttons.' . $button . '.pathImageModule = ' . GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('rtehtmlarea_wizard_select_image')) . ';';
        }
        return implode(LF, $jsArray);
    }
}
