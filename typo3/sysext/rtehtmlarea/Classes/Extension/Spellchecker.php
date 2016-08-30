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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi;

/**
 * Spell Checker plugin for htmlArea RTE
 */
class Spellchecker extends RteHtmlAreaApi
{
    /**
     * The name of the plugin registered by the extension
     *
     * @var string
     */
    protected $pluginName = 'SpellChecker';

    /**
     * The comma-separated list of button names that the registered plugin is adding to the htmlArea RTE toolbar
     *
     * @var string
     */
    protected $pluginButtons = 'spellcheck';

    /**
     * The name-converting array, converting the button names used in the RTE PageTSConfing to the button id's used by the JS scripts
     *
     * @var array
     */
    protected $convertToolbarForHtmlAreaArray = [
        'spellcheck' => 'SpellCheck'
    ];

    /**
     * Spell checker modes
     *
     * @var array
     */
    protected $spellCheckerModes = ['ultra', 'fast', 'normal', 'bad-spellers'];

    /**
     * Returns TRUE if the plugin is available and correctly initialized
     *
     * @param array $configuration Configuration array given from calling object down to the single plugins
     * @return bool TRUE if this plugin object should be made available in the current environment and is correctly initialized
     */
    public function main(array $configuration)
    {
        return parent::main($configuration)
            && ExtensionManagementUtility::isLoaded('static_info_tables')
            && !in_array(
                $this->configuration['language'],
                GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['SpellChecker']['noSpellCheckLanguages'])
            );
    }

    /**
     * Return JS configuration of the htmlArea plugins registered by the extension
     *
     * @return string JS configuration for registered plugins
     */
    public function buildJavascriptConfiguration()
    {
        $jsArray = [];
        $button = 'spellcheck';
        // Set the SpellChecker mode
        $spellCheckerMode = isset($GLOBALS['BE_USER']->userTS['options.']['HTMLAreaPspellMode']) ? trim($GLOBALS['BE_USER']->userTS['options.']['HTMLAreaPspellMode']) : 'normal';
        if (!in_array($spellCheckerMode, $this->spellCheckerModes)) {
            $spellCheckerMode = 'normal';
        }
        // Set the use of personal dictionary
        $enablePersonalDicts = $this->configuration['thisConfig']['buttons.'][$button . '.']['enablePersonalDictionaries'] && !empty($GLOBALS['BE_USER']->userTS['options.']['enablePersonalDicts']);
        if (in_array($button, $this->toolbar)) {
            if (!is_array($this->configuration['thisConfig']['buttons.']) || !is_array($this->configuration['thisConfig']['buttons.'][$button . '.'])) {
                $jsArray[] = 'RTEarea[editornumber].buttons.' . $button . ' = new Object();';
            }
            $jsArray[] = 'RTEarea[editornumber].buttons.' . $button . '.contentTypo3Language = "' . $this->configuration['contentTypo3Language'] . '";';
            $jsArray[] = 'RTEarea[editornumber].buttons.' . $button . '.contentISOLanguage = "' . $this->configuration['contentISOLanguage'] . '";';
            $jsArray[] = 'RTEarea[editornumber].buttons.' . $button . '.spellCheckerMode = "' . $spellCheckerMode . '";';
            $jsArray[] = 'RTEarea[editornumber].buttons.' . $button . '.enablePersonalDicts = ' . ($enablePersonalDicts ? 'true' : 'false') . ';';
            $jsArray[] = 'RTEarea[editornumber].buttons.' . $button . '.path = "' . ($this->isFrontend() || $this->isFrontendEditActive() ? ($GLOBALS['TSFE']->absRefPrefix ? $GLOBALS['TSFE']->absRefPrefix : '') . 'index.php?eID=rtehtmlarea_spellchecker' : BackendUtility::getAjaxUrl('rtehtmlarea_spellchecker')) . '";';
        }
        return implode(LF, $jsArray);
    }
}
