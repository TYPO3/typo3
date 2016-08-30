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
 * TYPO3Link plugin for htmlArea RTE
 */
class Typo3Link extends RteHtmlAreaApi
{
    /**
     * The name of the plugin registered by the extension
     *
     * @var string
     */
    protected $pluginName = 'TYPO3Link';

    /**
     * The comma-separated list of button names that the registered plugin is adding to the htmlArea RTE toolbar
     *
     * @var string
     */
    protected $pluginButtons = 'link, unlink';

    /**
     * The name-converting array, converting the button names used in the RTE PageTSConfing to the button id's used by the JS scripts
     *
     * @var array
     */
    protected $convertToolbarForHtmlAreaArray = [
        'link' => 'CreateLink',
        'unlink' => 'UnLink'
    ];

    /**
     * Returns TRUE if the plugin is available and correctly initialized
     *
     * @param array $configuration Configuration array given from calling object down to the single plugins
     * @return bool TRUE if this plugin object should be made available in the current environment and is correctly initialized
     */
    public function main(array $configuration)
    {
        // Check if this should be enabled based on Page TSConfig
        return parent::main($configuration)
            && !$this->configuration['thisConfig']['buttons.']['link.']['TYPO3Browser.']['disabled'];
    }

    /**
     * Return JS configuration of the htmlArea plugins registered by the extension
     *
     * @return string JS configuration for registered plugins, in this case, JS configuration of block elements
     */
    public function buildJavascriptConfiguration()
    {
        $jsArray = [];
        $button = 'link';
        if (in_array($button, $this->toolbar)) {
            if (!is_array($this->configuration['thisConfig']['buttons.']) || !is_array($this->configuration['thisConfig']['buttons.'][$button . '.'])) {
                $jsArray[] = 'RTEarea[editornumber].buttons.' . $button . ' = new Object();';
            }
            $jsArray[] = 'RTEarea[editornumber].buttons.' . $button . '.pathLinkModule = ' . GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('rtehtmlarea_wizard_browse_links')) . ';';
            if (is_array($this->configuration['RTEsetup']['properties']['classesAnchor.'])) {
                $jsArray[] = 'RTEarea[editornumber].buttons.' . $button . '.classesAnchorUrl = "' . $this->writeTemporaryFile('classesAnchor_' . $this->configuration['contentLanguageUid'], 'js', $this->buildJSClassesAnchorArray()) . '";';
            }
            $jsArray[] = 'RTEarea[editornumber].buttons.' . $button . '.additionalAttributes = "data-htmlarea-external' . ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extensionKey]['plugins'][$this->pluginName]['additionalAttributes'] ? ',' . $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extensionKey]['plugins'][$this->pluginName]['additionalAttributes'] : '') . '";';
        }
        return implode(LF, $jsArray);
    }

    /**
     * Return a JS array for special anchor classes
     *
     * @return string classesAnchor array definition
     */
    public function buildJSClassesAnchorArray()
    {
        $JSClassesAnchorArray = 'HTMLArea.classesAnchorSetup = [ ' . LF;
        $classesAnchorIndex = 0;
        foreach ($this->configuration['RTEsetup']['properties']['classesAnchor.'] as $label => $conf) {
            if (is_array($conf) && $conf['class']) {
                $JSClassesAnchorArray .= ($classesAnchorIndex++ ? ',' : '') . ' { ' . LF;
                $index = 0;
                $JSClassesAnchorArray .= ($index++ ? ',' : '') . 'name : "' . str_replace('"', '', str_replace('\'', '', $conf['class'])) . '"' . LF;
                if ($conf['type']) {
                    $JSClassesAnchorArray .= ($index++ ? ',' : '') . 'type : "' . str_replace('"', '', str_replace('\'', '', $conf['type'])) . '"' . LF;
                }
                if (trim(str_replace('\'', '', str_replace('"', '', $conf['image'])))) {
                    $JSClassesAnchorArray .= ($index++ ? ',' : '') . 'image : "' . GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . GeneralUtility::resolveBackPath((TYPO3_mainDir . $this->getFullFileName(trim(str_replace('\'', '', str_replace('"', '', $conf['image'])))))) . '"' . LF;
                }
                $JSClassesAnchorArray .= ($index++ ? ',' : '') . 'addIconAfterLink : ' . ($conf['addIconAfterLink'] ? 'true' : 'false') . LF;
                if (trim($conf['altText'])) {
                    $string = GeneralUtility::quoteJSvalue($this->getLanguageService()->sL(trim($conf['altText'])));
                    $JSClassesAnchorArray .= ($index++ ? ',' : '') . 'altText : ' . str_replace('"', '\\"', str_replace('\\\'', '\'', $string)) . LF;
                }
                if (trim($conf['titleText'])) {
                    $string = GeneralUtility::quoteJSvalue($this->getLanguageService()->sL(trim($conf['titleText'])));
                    $JSClassesAnchorArray .= ($index++ ? ',' : '') . 'titleText : ' . str_replace('"', '\\"', str_replace('\\\'', '\'', $string)) . LF;
                }
                if (trim($conf['target'])) {
                    $JSClassesAnchorArray .= ($index++ ? ',' : '') . 'target : "' . trim($conf['target']) . '"' . LF;
                }
                $JSClassesAnchorArray .= '}' . LF;
            }
        }
        $JSClassesAnchorArray .= '];' . LF;
        return $JSClassesAnchorArray;
    }

    /**
     * Return an updated array of toolbar enabled buttons
     *
     * @param array $show: array of toolbar elements that will be enabled, unless modified here
     * @return array toolbar button array, possibly updated
     */
    public function applyToolbarConstraints($show)
    {
        // We will not allow unlink if link is not enabled
        if (!in_array('link', $show)) {
            return array_diff($show, GeneralUtility::trimExplode(',', $this->pluginButtons));
        } else {
            return $show;
        }
    }
}
