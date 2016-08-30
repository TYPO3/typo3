<?php
namespace TYPO3\CMS\Rtehtmlarea;

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

use TYPO3\CMS\Core\FrontendEditing\FrontendEditingController;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * API for extending htmlArea RTE
 */
abstract class RteHtmlAreaApi
{
    /**
     * The key of the extension that is extending htmlArea RTE
     *
     * @var string
     */
    protected $extensionKey = 'rtehtmlarea';

    /**
     * The name of the plugin registered by the extension
     *
     * @var string
     */
    protected $pluginName;

    /**
     * Path to the skin (css) file that should be added to the RTE skin when the registered plugin is enabled, relative to the extension dir
     *
     * @var string
     */
    protected $relativePathToSkin = '';

    /**
     * Toolbar array
     *
     * @var array
     */
    protected $toolbar;

    /**
     * The comma-separated list of button names that the registered plugin is adding to the htmlArea RTE toolbar
     *
     * @var string
     */
    protected $pluginButtons = '';

    /**
     * The comma-separated list of label names that the registered plugin is adding to the htmlArea RTE toolbar
     *
     * @var string
     */
    protected $pluginLabels = '';

    /**
     * Boolean indicating whether the plugin is adding buttons or not
     *
     * @var bool
     */
    protected $pluginAddsButtons = true;

    /**
     * The name-converting array, converting the button names used in the RTE PageTSConfing to the button id's used by the JS scripts
     *
     * @var array
     */
    protected $convertToolbarForHtmlAreaArray = [];

    /**
     * TRUE if the registered plugin requires the PageTSConfig Classes configuration
     *
     * @var bool
     */
    protected $requiresClassesConfiguration = false;

    /**
     * The comma-separated list of names of prerequisite plugins
     *
     * @var string
     */
    protected $requiredPlugins = '';

    /**
     * Configuration array with settings given down from calling class
     *
     * @var array
     */
    protected $configuration;

    /**
     * Returns TRUE if the plugin is available and correctly initialized
     *
     * @param array $configuration Configuration array given from calling object down to the single plugins
     * @return bool TRUE if this plugin object should be made available in the current environment and is correctly initialized
     */
    public function main(array $configuration)
    {
        $this->configuration = $configuration;
        // Set the value of this boolean based on the initial value of $this->pluginButtons
        $this->pluginAddsButtons = !empty($this->pluginButtons);
        // Check if the plugin should be disabled in frontend
        if ($this->isFrontend() && $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins'][$this->pluginName]['disableInFE']) {
            return false;
        }
        return true;
    }

    /**
     * Return JS configuration of the htmlArea plugins registered by the extension
     *
     * @return string JS configuration for registered plugins
     */
    public function buildJavascriptConfiguration()
    {
        $jsArray = [];
        $pluginButtons = GeneralUtility::trimExplode(',', $this->pluginButtons, true);
        foreach ($pluginButtons as $button) {
            if (in_array($button, $this->toolbar)) {
                if (!is_array($this->configuration['thisConfig']['buttons.']) || !is_array($this->configuration['thisConfig']['buttons.'][$button . '.'])) {
                    $jsArray[] = 'RTEarea[editornumber].buttons.' . $button . ' = new Object();';
                }
            }
        }
        return implode(LF, $jsArray);
    }

    /**
     * Returns the extension key
     *
     * @return string the extension key
     */
    public function getExtensionKey()
    {
        return $this->extensionKey;
    }

    /**
     * Returns a boolean indicating whether the plugin adds buttons or not to the toolbar
     *
     * @return bool
     */
    public function addsButtons()
    {
        return $this->pluginAddsButtons;
    }

    /**
     * Returns the list of buttons implemented by the plugin
     *
     * @return string the list of buttons implemented by the plugin
     */
    public function getPluginButtons()
    {
        return $this->pluginButtons;
    }

    /**
     * Returns the list of toolbar labels implemented by the plugin
     *
     * @return string the list of labels implemented by the plugin
     */
    public function getPluginLabels()
    {
        return $this->pluginLabels;
    }

    /**
     * Returns the conversion array from TYPO3 button names to htmlArea button names
     *
     * @return array the conversion array from TYPO3 button names to htmlArea button names
     */
    public function getConvertToolbarForHtmlAreaArray()
    {
        return $this->convertToolbarForHtmlAreaArray;
    }

    /**
     * Returns TRUE if the extension requires the PageTSConfig Classes configuration
     *
     * @return bool TRUE if the extension requires the PageTSConfig Classes configuration
     */
    public function requiresClassesConfiguration()
    {
        return $this->requiresClassesConfiguration;
    }

    /**
     * Returns the list of plugins required by the plugin
     *
     * @return string the list of plugins required by the plugin
     */
    public function getRequiredPlugins()
    {
        return $this->requiredPlugins;
    }

    /**
     * Set toolbal
     *
     * @param array $toolbar
     */
    public function setToolbar(array $toolbar)
    {
        $this->toolbar = $toolbar;
    }

    /**
     * Clean list
     *
     * @param string $str
     * @return string
     */
    protected function cleanList($str)
    {
        if (strstr($str, '*')) {
            $str = '*';
        } else {
            $str = implode(',', array_unique(GeneralUtility::trimExplode(',', $str, true)));
        }
        return $str;
    }

    /**
     * Resolve a label and do some funny quoting.
     *
     * @param string $string Given label name
     * @return string Resolved label
     */
    protected function getPageConfigLabel($string)
    {
        $label = $this->getLanguageService()->sL(trim($string));
        // @todo: find out why this is done and if it could be substituted with quoteJSvalue
        $label = str_replace('"', '\\"', str_replace('\\\'', '\'', $label));
        return $label;
    }

    /**
     * Return TRUE if we are in the FE, but not in the FE editing feature of BE.
     *
     * @return bool
     */
    protected function isFrontend()
    {
        return is_object($GLOBALS['TSFE'])
            && !$this->isFrontendEditActive()
            && TYPO3_MODE == 'FE';
    }

    /**
     * Checks whether frontend editing is active.
     *
     * @return bool
     */
    protected function isFrontendEditActive()
    {
        return is_object($GLOBALS['TSFE'])
            && $GLOBALS['TSFE']->beUserLogin
            && $GLOBALS['BE_USER']->frontendEdit instanceof FrontendEditingController;
    }

    /**
     * Make a file name relative to the PATH_site or to the PATH_typo3
     *
     * @param string $filename: a file name of the form EXT:.... or relative to the PATH_site
     * @return string the file name relative to the PATH_site if in frontend or relative to the PATH_typo3 if in backend
     */
    protected function getFullFileName($filename)
    {
        if (substr($filename, 0, 4) === 'EXT:') {
            // extension
            list($extKey, $local) = explode('/', substr($filename, 4), 2);
            $newFilename = '';
            if ((string)$extKey !== '' && ExtensionManagementUtility::isLoaded($extKey) && (string)$local !== '') {
                $newFilename = ($this->isFrontend() || $this->isFrontendEditActive()
                        ? ExtensionManagementUtility::siteRelPath($extKey)
                        : ExtensionManagementUtility::extRelPath($extKey))
                    . $local;
            }
        } else {
            $path = ($this->isFrontend() || $this->isFrontendEditActive() ? '' : '../');
            $newFilename = $path . ($filename[0] === '/' ? substr($filename, 1) : $filename);
        }
        return GeneralUtility::resolveBackPath($newFilename);
    }

    /**
     * Writes contents in a file in typo3temp and returns the file name
     *
     * @param string $label: A label to insert at the beginning of the name of the file
     * @param string $fileExtension: The file extension of the file, defaulting to 'js'
     * @param string $contents: The contents to write into the file
     * @return string The name of the file written to typo3temp
     * @throws \RuntimeException If writing to file failed
     */
    protected function writeTemporaryFile($label, $fileExtension = 'js', $contents = '')
    {
        $relativeFilename = 'typo3temp/RteHtmlArea/' . str_replace('-', '_', $label) . '_' . GeneralUtility::shortMD5($contents, 20) . '.' . $fileExtension;
        $destination = PATH_site . $relativeFilename;
        if (!file_exists($destination)) {
            $minifiedJavaScript = '';
            if ($fileExtension === 'js' && $contents !== '') {
                $minifiedJavaScript = GeneralUtility::minifyJavaScript($contents);
            }
            $failure = GeneralUtility::writeFileToTypo3tempDir($destination, $minifiedJavaScript ? $minifiedJavaScript : $contents);
            if ($failure) {
                throw new \RuntimeException($failure, 1294585668);
            }
        }
        if ($this->isFrontend() || $this->isFrontendEditActive()) {
            $fileName = $relativeFilename;
        } else {
            $fileName = '../' . $relativeFilename;
        }
        return GeneralUtility::resolveBackPath($fileName);
    }

    /**
     * Get language service, instantiate if not there, yet
     *
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
