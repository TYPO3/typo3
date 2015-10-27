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
 * Contains QTOBJECT content object.
 */
class QuicktimeObjectContentObject extends \TYPO3\CMS\Frontend\ContentObject\AbstractContentObject
{
    /**
     * Rendering the cObject, QTOBJECT
     *
     * @param array $conf Array of TypoScript properties
     * @return string Output
     */
    public function render($conf = array())
    {
        $params = ($prefix = '');
        if ($GLOBALS['TSFE']->baseUrl) {
            $prefix = $GLOBALS['TSFE']->baseUrl;
        }
        if ($GLOBALS['TSFE']->absRefPrefix) {
            $prefix = $GLOBALS['TSFE']->absRefPrefix;
        }
        $type = isset($conf['type.']) ? $this->cObj->stdWrap($conf['type'], $conf['type.']) : $conf['type'];

        // If file is audio and an explicit path has not been set,
        // take path from audio fallback property
        if ($type == 'audio' && empty($conf['file'])) {
            $conf['file'] = $conf['audioFallback'];
        }
        $filename = isset($conf['file.'])
            ? $this->cObj->stdWrap($conf['file'], $conf['file.'])
            : $conf['file'];

        $typeConf = $conf[$type . '.'];
        // Add QTobject js-file
        $this->getPageRenderer()->addJsFile($this->getPathToLibrary('flashmedia/qtobject/qtobject.js'));
        $replaceElementIdString = StringUtility::getUniqueId('mmqt');
        $GLOBALS['TSFE']->register['MMQTID'] = $replaceElementIdString;
        $qtObject = 'QTObject' . $replaceElementIdString;
        // Merge with default parameters
        $conf['params.'] = array_merge((array)$typeConf['default.']['params.'], (array)$conf['params.']);
        if (is_array($conf['params.']) && is_array($typeConf['mapping.']['params.'])) {
            ArrayUtility::remapArrayKeys($conf['params.'], $typeConf['mapping.']['params.']);
            foreach ($conf['params.'] as $key => $value) {
                $params .= $qtObject . '.addParam("' . $key . '", "' . $value . '");' . LF;
            }
        }
        $params = ($params ? substr($params, 0, -2) : '') . LF . $qtObject . '.write("' . $replaceElementIdString . '");';
        $alternativeContent = isset($conf['alternativeContent.']) ? $this->cObj->stdWrap($conf['alternativeContent'], $conf['alternativeContent.']) : $conf['alternativeContent'];
        $layout = str_replace(
            array(
                '###ID###',
                '###QTOBJECT###'
            ),
            array(
                $replaceElementIdString,
                '<div id="' . $replaceElementIdString . '">' . $alternativeContent . '</div>'
            ),
            isset($conf['layout.']) ? $this->cObj->stdWrap($conf['layout'], $conf['layout.']) : $conf['layout']
        );
        $width = isset($conf['width.']) ? $this->cObj->stdWrap($conf['width'], $conf['width.']) : $conf['width'];
        if (!$width) {
            $width = $conf[$type . '.']['defaultWidth'];
        }
        $height = isset($conf['height.']) ? $this->cObj->stdWrap($conf['height'], $conf['height.']) : $conf['height'];
        if (!$height) {
            $height = $conf[$type . '.']['defaultHeight'];
        }
        $fullFilename = $filename;
        // If the file name doesn't contain a scheme, prefix with appropriate data
        if (strpos($filename, '://') === false && !empty($prefix)) {
            $fullFilename = $prefix . $filename;
        }
        $embed = 'var ' . $qtObject . ' = new QTObject("' . $fullFilename . '", "' . $replaceElementIdString . '", "' . $width . '", "' . $height . '");';
        $content = $layout . '
			<script type="text/javascript">
				' . $embed . '
				' . $params . '
			</script>';
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
