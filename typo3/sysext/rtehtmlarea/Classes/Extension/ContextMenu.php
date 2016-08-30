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
 * Context Menu plugin for htmlArea RTE
 */
class ContextMenu extends RteHtmlAreaApi
{
    /**
     * The name of the plugin registered by the extension
     *
     * @var string
     */
    protected $pluginName = 'ContextMenu';

    /**
     * Returns TRUE if the plugin is available and correctly initialized
     *
     * @param array $configuration Configuration array given from calling object down to the single plugins
     * @return bool TRUE if this plugin object should be made available in the current environment and is correctly initialized
     */
    public function main(array $configuration)
    {
        return parent::main($configuration)
            && !($this->configuration['client']['browser'] === 'opera' || $this->configuration['thisConfig']['contextMenu.']['disabled']);
    }

    /**
     * Return JS configuration of the htmlArea plugins registered by the extension
     *
     * @return string JS configuration for registered plugins
     */
    public function buildJavascriptConfiguration()
    {
        $jsArray = [];
        if (is_array($this->configuration['thisConfig']['contextMenu.'])) {
            $jsArray[] = 'RTEarea[editornumber].contextMenu =  ' . $this->buildNestedJSArray($this->configuration['thisConfig']['contextMenu.']) . ';';
            if ($this->configuration['thisConfig']['contextMenu.']['showButtons']) {
                $jsArray[] = 'RTEarea[editornumber].contextMenu.showButtons = ' . json_encode(GeneralUtility::trimExplode(',', $this->cleanList(GeneralUtility::strtolower($this->configuration['thisConfig']['contextMenu.']['showButtons'])), true)) . ';';
            }
            if ($this->configuration['thisConfig']['contextMenu.']['hideButtons']) {
                $jsArray[] = 'RTEarea[editornumber].contextMenu.hideButtons = ' . json_encode(GeneralUtility::trimExplode(',', $this->cleanList(GeneralUtility::strtolower($this->configuration['thisConfig']['contextMenu.']['hideButtons'])), true)) . ';';
            }
        }
        return implode(LF, $jsArray);
    }

    /**
     * Translate Page TS Config array in JS nested array definition
     * Replace 0 values with false
     * Unquote regular expression values
     * Replace empty arrays with empty objects
     *
     * @param array $conf: Page TSConfig configuration array
     * @return string nested JS array definition
     */
    protected function buildNestedJSArray($conf)
    {
        $convertedConf = GeneralUtility::removeDotsFromTS($conf);
        return str_replace([':"0"', ':"\\/^(', ')$\\/i"', ':"\\/^(', ')$\\/"', '[]'], [':false', ':/^(', ')$/i', ':/^(', ')$/', '{}'], json_encode($convertedConf));
    }
}
