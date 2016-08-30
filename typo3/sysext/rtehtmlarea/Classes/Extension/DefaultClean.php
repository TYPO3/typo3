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
 * Default Clean extension for htmlArea RTE
 */
class DefaultClean extends RteHtmlAreaApi
{
    /**
     * The name of the plugin registered by the extension
     *
     * @var string
     */
    protected $pluginName = 'DefaultClean';

    /**
     * The comma-separated list of button names that the registered plugin is adding to the htmlArea RTE toolbar
     *
     * @var string
     */
    protected $pluginButtons = 'cleanword';

    /**
     * The name-converting array, converting the button names used in the RTE PageTSConfing to the button id's used by the JS scripts
     *
     * @var array
     */
    protected $convertToolbarForHtmlAreaArray = [
        'cleanword' => 'CleanWord'
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
            && $this->configuration['thisConfig']['enableWordClean']
            && !is_array($this->configuration['thisConfig']['enableWordClean.']['HTMLparser.']);
    }

    /**
     * Return JS configuration of the htmlArea plugins registered by the extension
     *
     * @return string JS configuration for registered plugins, in this case, JS configuration of block elements
     */
    public function buildJavascriptConfiguration()
    {
        $jsArray = [];
        $button = 'cleanword';
        if (in_array($button, $this->toolbar)) {
            if (!is_array($this->configuration['thisConfig']['buttons.']) || !is_array($this->configuration['thisConfig']['buttons.'][$button . '.'])) {
                $jsArray[] = 'RTEarea[editornumber].buttons.' . $button . ' = new Object();';
            }
            $jsArray[] = 'RTEarea[editornumber].buttons.' . $button . ' = {"hotKey" : "' . ($this->configuration['thisConfig']['enableWordClean.']['hotKey'] ? $this->configuration['thisConfig']['enableWordClean.']['hotKey'] : '0') . '"};';
        }
        return implode(LF, $jsArray);
    }

    /**
     * Return an updated array of toolbar enabled buttons
     * Force inclusion of hidden button cleanword
     *
     * @param array $show: array of toolbar elements that will be enabled, unless modified here
     * @return array toolbar button array, possibly updated
     */
    public function applyToolbarConstraints($show)
    {
        return array_unique(array_merge($show, GeneralUtility::trimExplode(',', $this->pluginButtons)));
    }
}
