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

/**
 * Contains RESTORE_REGISTER class object.
 */
class ScalableVectorGraphicsContentObject extends AbstractContentObject
{
    /**
     * Rendering the cObject, SVG
     *
     * @param array $conf Array of TypoScript properties
     * @return string Empty string (the cObject only sets internal data!)
     */
    public function render($conf = [])
    {
        $width = isset($conf['width.']) ? $this->cObj->stdWrap($conf['width'], $conf['width.']) : $conf['width'];
        if (!$width) {
            $width = 600;
        }
        $height = isset($conf['height.']) ? $this->cObj->stdWrap($conf['height'], $conf['height.']) : $conf['height'];
        if (!$height) {
            $height = 400;
        }
        $src = isset($conf['src.']) ? $this->cObj->stdWrap($conf['src'], $conf['src.']) : $conf['src'];
        if (!$src) {
            $src = null;
        }
        $value = isset($conf['value.']) ? $this->cObj->stdWrap($conf['value'], $conf['value.']) : $conf['value'];
        $noscript = isset($conf['noscript.']) ? $this->cObj->stdWrap($conf['noscript'], $conf['noscript.']) : $conf['noscript'];
        if ($src) {
            $content = '

					<!--[if IE]>
					<object src="' . $src . '" classid="image/svg+xml" width="' . $width . '" height="' . $height . '">
					<![endif]-->
					<!--[if !IE]>-->
					<object data="' . $src . '" type="image/svg+xml" width="' . $width . '" height="' . $height . '">
					<!--<![endif]-->
					' . $noscript . '
					</object>

			';
        } else {
            $content = '
				<script type="image/svg+xml">
					<svg xmlns="http://www.w3.org/2000/svg"
					xmlns:xlink="http://www.w3.org/1999/xlink"
					width="' . $width . '"
					height="' . $height . '">
			' . $value . '
				</svg>
				</script>
				<noscript>
			' . $noscript . '
				</noscript>
			';
        }
        if (isset($conf['stdWrap.'])) {
            $content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
        }
        return $content;
    }
}
