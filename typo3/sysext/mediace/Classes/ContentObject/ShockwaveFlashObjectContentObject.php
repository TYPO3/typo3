<?php
namespace FoT3\Mediace\ContentObject;

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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Contains SWFOBJECT content object.
 */
class ShockwaveFlashObjectContentObject extends \TYPO3\CMS\Frontend\ContentObject\AbstractContentObject
{
    /**
     * Rendering the cObject, SWFOBJECT
     *
     * @param array $conf Array of TypoScript properties
     * @return string Output
     */
    public function render($conf = array())
    {
        $prefix = '';
        if ($GLOBALS['TSFE']->baseUrl) {
            $prefix = $GLOBALS['TSFE']->baseUrl;
        }
        if ($GLOBALS['TSFE']->absRefPrefix) {
            $prefix = $GLOBALS['TSFE']->absRefPrefix;
        }
        $type = isset($conf['type.']) ? $this->cObj->stdWrap($conf['type'], $conf['type.']) : $conf['type'];
        $typeConf = $conf[$type . '.'];

        // Add SWFobject js-file
        $this->getPageRenderer()->addJsFile($this->getPathToLibrary('flashmedia/swfobject/swfobject.js'));
        $player = isset($typeConf['player.']) ? $this->cObj->stdWrap($typeConf['player'], $typeConf['player.']) : $typeConf['player'];
        if (strpos($player, 'EXT:') === 0) {
            $player = $prefix . $GLOBALS['TSFE']->tmpl->getFileName($player);
        }
        $installUrl = isset($conf['installUrl.']) ? $this->cObj->stdWrap($conf['installUrl'], $conf['installUrl.']) : $conf['installUrl'];
        if (!$installUrl) {
            $installUrl = $prefix . $this->getPathToLibrary('flashmedia/swfobject/expressInstall.swf');
        }
        // If file is audio and an explicit path has not been set,
        // take path from audio fallback property
        if ($type == 'audio' && empty($conf['file'])) {
            $conf['file'] = $conf['audioFallback'];
        }
        $filename = isset($conf['file.']) ? $this->cObj->stdWrap($conf['file'], $conf['file.']) : $conf['file'];
        $forcePlayer = isset($conf['forcePlayer.']) ? $this->cObj->stdWrap($conf['forcePlayer'], $conf['forcePlayer.']) : $conf['forcePlayer'];
        if ($filename && $forcePlayer) {
            if (strpos($filename, '://') !== false) {
                $conf['flashvars.']['file'] = $filename;
            } else {
                if ($prefix) {
                    $conf['flashvars.']['file'] = $prefix . $filename;
                } else {
                    $conf['flashvars.']['file'] = str_repeat('../', substr_count($player, '/')) . $filename;
                }
            }
        } else {
            $player = $filename;
        }
        // Write calculated values in conf for the hook
        $conf['player'] = $player;
        $conf['installUrl'] = $installUrl;
        $conf['filename'] = $filename;
        $conf['prefix'] = $prefix;
        // Merge with default parameters
        $conf['flashvars.'] = array_merge((array)$typeConf['default.']['flashvars.'], (array)$conf['flashvars.']);
        $conf['params.'] = array_merge((array)$typeConf['default.']['params.'], (array)$conf['params.']);
        $conf['attributes.'] = array_merge((array)$typeConf['default.']['attributes.'], (array)$conf['attributes.']);
        $conf['embedParams'] = 'flashvars, params, attributes';
        // Hook for manipulating the conf array, it's needed for some players like flowplayer
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/hooks/class.tx_cms_mediaitems.php']['swfParamTransform'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/hooks/class.tx_cms_mediaitems.php']['swfParamTransform'] as $classRef) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($classRef, $conf, $this);
            }
        }
        if (is_array($conf['flashvars.']) && is_array($typeConf['mapping.']['flashvars.'])) {
            ArrayUtility::remapArrayKeys($conf['flashvars.'], $typeConf['mapping.']['flashvars.']);
        }
        $flashvars = 'var flashvars = ' . (!empty($conf['flashvars.']) ? json_encode($conf['flashvars.']) : '{}') . ';';
        if (is_array($conf['params.']) && is_array($typeConf['mapping.']['params.'])) {
            ArrayUtility::remapArrayKeys($conf['params.'], $typeConf['mapping.']['params.']);
        }
        $params = 'var params = ' . (!empty($conf['params.']) ? json_encode($conf['params.']) : '{}') . ';';
        if (is_array($conf['attributes.']) && is_array($typeConf['attributes.']['params.'])) {
            ArrayUtility::remapArrayKeys($conf['attributes.'], $typeConf['attributes.']['params.']);
        }
        $attributes = 'var attributes = ' . (!empty($conf['attributes.']) ? json_encode($conf['attributes.']) : '{}') . ';';
        $flashVersion = isset($conf['flashVersion.']) ? $this->cObj->stdWrap($conf['flashVersion'], $conf['flashVersion.']) : $conf['flashVersion'];
        if (!$flashVersion) {
            $flashVersion = '9';
        }
        $replaceElementIdString = StringUtility::getUniqueId('mmswf');
        $GLOBALS['TSFE']->register['MMSWFID'] = $replaceElementIdString;
        $alternativeContent = isset($conf['alternativeContent.']) ? $this->cObj->stdWrap($conf['alternativeContent'], $conf['alternativeContent.']) : $conf['alternativeContent'];
        $layout = isset($conf['layout.']) ? $this->cObj->stdWrap($conf['layout'], $conf['layout.']) : $conf['layout'];
        $content = str_replace('###ID###', $replaceElementIdString, $layout);
        $content = str_replace('###SWFOBJECT###', '<div id="' . $replaceElementIdString . '">' . $alternativeContent . '</div>', $content);
        $width = isset($conf['width.']) ? $this->cObj->stdWrap($conf['width'], $conf['width.']) : $conf['width'];
        if (!$width) {
            $width = $conf[$type . '.']['defaultWidth'];
        }
        $height = isset($conf['height.']) ? $this->cObj->stdWrap($conf['height'], $conf['height.']) : $conf['height'];
        if (!$height) {
            $height = $conf[$type . '.']['defaultHeight'];
        }
        $embed = 'swfobject.embedSWF("' . $conf['player'] . '", "' . $replaceElementIdString . '", "' . $width . '", "' . $height . '",
				"' . $flashVersion . '", "' . $installUrl . '", ' . $conf['embedParams'] . ');';
        $script = $flashvars . $params . $attributes . $embed;
        $this->getPageRenderer()->addJsInlineCode($replaceElementIdString, $script);
        if (isset($conf['stdWrap.'])) {
            $content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
        }
        return $content;
    }

    /**
     * resolves the path to the extensions' Contrib directory
     *
     * @param string $fileAndFolderName the file to be located
     * @return string
     */
    protected function getPathToLibrary($fileAndFolderName)
    {
        return $GLOBALS['TSFE']->tmpl->getFileName('EXT:mediace/Resources/Contrib/' . $fileAndFolderName);
    }
}
