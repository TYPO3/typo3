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

use TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi;

/**
 * Copy as Plain Text extension for htmlArea RTE
 */
class Plaintext extends RteHtmlAreaApi
{
    /**
     * The name of the plugin registered by the extension
     *
     * @var string
     */
    protected $pluginName = 'PlainText';

    /**
     * The comma-separated list of button names that the registered plugin is adding to the htmlArea RTE toolbar
     *
     * @var string
     */
    protected $pluginButtons = 'pastetoggle,pastebehaviour';

    /**
     * The name-converting array, converting the button names used in the RTE PageTSConfing to the button id's used by the JS scripts
     *
     * @var array
     */
    protected $convertToolbarForHtmlAreaArray = [
        'pastetoggle' => 'PasteToggle',
        'pastebehaviour' => 'PasteBehaviour'
    ];

    /**
     * Returns TRUE if the plugin is available and correctly initialized
     *
     * @param array $configuration Configuration array given from calling object down to the single plugins
     * @return bool TRUE if this plugin object should be made available in the current environment and is correctly initialized
     */
    public function main(array $configuration)
    {
        // Opera has no onPaste event to handle
        return parent::main($configuration)
            && $this->configuration['client']['browser'] !== 'opera';
    }

    /**
     * Return JS configuration of the htmlArea plugins registered by the extension
     *
     * @return string JS configuration for registered plugins
     */
    public function buildJavascriptConfiguration()
    {
        $jsArray = [];
        $button = 'pastebehaviour';
        // Get current TYPO3 User Setting, if available
        if (TYPO3_MODE === 'BE' && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('setup') && is_array($GLOBALS['TYPO3_USER_SETTINGS']) && is_object($GLOBALS['BE_USER'])) {
            if (!is_array($this->configuration['thisConfig']['buttons.']) || !is_array($this->configuration['thisConfig']['buttons.'][$button . '.'])) {
                $jsArray[] = 'RTEarea[editornumber].buttons.' . $button . ' = new Object();';
            }
            $jsArray[] = 'RTEarea[editornumber].buttons.' . $button . '.current = "' . (isset($GLOBALS['BE_USER']->uc['rteCleanPasteBehaviour']) ? $GLOBALS['BE_USER']->uc['rteCleanPasteBehaviour'] : 'plainText') . '";';
        }
        return implode(LF, $jsArray);
    }

    /**
     * Return an updated array of toolbar enabled buttons
     *
     * @param array $show: array of toolbar elements that will be enabled, unless modified here
     * @return array toolbar button array, possibly updated
     */
    public function applyToolbarConstraints($show)
    {
        $removeButtons = [];
        // Remove pastebehaviour button if pastetoggle is not configured
        if (!in_array('pastetoggle', $show)) {
            $removeButtons[] = 'pastebehaviour';
        }
        // Remove pastebehaviour button if TYPO3 User Settings are available
        if (TYPO3_MODE === 'BE' && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('setup') && is_array($GLOBALS['TYPO3_USER_SETTINGS']) && is_object($GLOBALS['BE_USER'])) {
            $removeButtons[] = 'pastebehaviour';
        }
        return array_diff($show, $removeButtons);
    }
}
