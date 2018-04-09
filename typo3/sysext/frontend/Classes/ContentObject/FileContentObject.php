<?php
namespace TYPO3\CMS\Frontend\ContentObject;

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
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Contains FILE class object.
 */
class FileContentObject extends AbstractContentObject
{
    /**
     * Rendering the cObject, FILE
     *
     * @param array $conf Array of TypoScript properties
     * @return string Output
     */
    public function render($conf = [])
    {
        $theValue = '';
        $file = isset($conf['file.']) ? $this->cObj->stdWrap($conf['file'], $conf['file.']) : $conf['file'];
        $file = $this->getTypoScriptFrontendController()->tmpl->getFileName($file);
        if ($file !== null && file_exists($file)) {
            $fileInfo = GeneralUtility::split_fileref($file);
            $extension = $fileInfo['fileext'];
            if ($extension === 'jpg' || $extension === 'jpeg' || $extension === 'gif' || $extension === 'png') {
                $imageInfo = GeneralUtility::makeInstance(ImageInfo::class, $file);
                $altParameters = trim($this->cObj->getAltParam($conf, false));
                $theValue = '<img src="'
                            . htmlspecialchars($this->getTypoScriptFrontendController()->absRefPrefix . $file)
                            . '" width="' . (int)$imageInfo->getWidth() . '" height="' . (int)$imageInfo->getHeight()
                            . '"' . $this->cObj->getBorderAttr(' border="0"') . ' ' . $altParameters . ' />';
            } elseif (filesize($file) < 1024 * 1024) {
                $theValue = file_get_contents($file);
            }
        }

        $linkWrap = isset($conf['linkWrap.']) ? $this->cObj->stdWrap($conf['linkWrap'], $conf['linkWrap.']) : $conf['linkWrap'];
        if ($linkWrap) {
            $theValue = $this->cObj->linkWrap($theValue, $linkWrap);
        }
        $wrap = isset($conf['wrap.']) ? $this->cObj->stdWrap($conf['wrap'], $conf['wrap.']) : $conf['wrap'];
        if ($wrap) {
            $theValue = $this->cObj->wrap($theValue, $wrap);
        }
        if (isset($conf['stdWrap.'])) {
            $theValue = $this->cObj->stdWrap($theValue, $conf['stdWrap.']);
        }
        return $theValue;
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}
