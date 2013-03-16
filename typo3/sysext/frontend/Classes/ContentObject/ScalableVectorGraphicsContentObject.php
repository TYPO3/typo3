<?php
namespace TYPO3\CMS\Frontend\ContentObject;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Xavier Perseguers <typo3@perseguers.ch>
 *  (c) 2010-2013 Steffen Kamper <steffen@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Contains RESTORE_REGISTER class object.
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 */
class ScalableVectorGraphicsContentObject extends \TYPO3\CMS\Frontend\ContentObject\AbstractContentObject {

	/**
	 * Rendering the cObject, SVG
	 *
	 * @param array $conf Array of TypoScript properties
	 * @return string Empty string (the cObject only sets internal data!)
	 */
	public function render($conf = array()) {
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
			$src = NULL;
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
		$GLOBALS['TSFE']->getPageRenderer()->loadSvg();
		if (isset($conf['stdWrap.'])) {
			$content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
		}
		return $content;
	}

}


?>