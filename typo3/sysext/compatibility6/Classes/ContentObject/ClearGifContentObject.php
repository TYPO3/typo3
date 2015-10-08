<?php
namespace TYPO3\CMS\Compatibility6\ContentObject;

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

/**
 * Contains CLEARGIF class object.
 */
class ClearGifContentObject extends \TYPO3\CMS\Frontend\ContentObject\AbstractContentObject
{
    /**
     * Rendering the cObject, CLEARGIF
     *
     * @param array $conf Array of TypoScript properties
     * @return string Output
     */
    public function render($conf = array())
    {
        $width = isset($conf['width.']) ? $this->cObj->stdWrap($conf['width'], $conf['width.']) : $conf['width'];
        if (!$width) {
            $width = 1;
        }
        $height = isset($conf['height.']) ? $this->cObj->stdWrap($conf['height'], $conf['height.']) : $conf['height'];
        if (!$height) {
            $height = 1;
        }
        $wrap = isset($conf['wrap.']) ? $this->cObj->stdWrap($conf['wrap'], $conf['wrap.']) : $conf['wrap'];
        if (!$wrap) {
            $wrap = '|<br />';
        }
        $theValue = $this->cObj->wrap('<span style="width: ' . $width . 'px; height: ' . $height . 'px;"></span>', $wrap);
        if (isset($conf['stdWrap.'])) {
            $theValue = $this->cObj->stdWrap($theValue, $conf['stdWrap.']);
        }
        return $theValue;
    }
}
