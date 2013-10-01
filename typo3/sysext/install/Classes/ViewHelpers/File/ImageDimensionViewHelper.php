<?php
namespace TYPO3\CMS\Install\ViewHelpers\File;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Get width or height from image file
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <f:file.imageDimension>/var/www/typo3/instance/typo3temp/foo.jpg</f:file.size>
 * </code>
 * <output>
 * 170
 * </output>
 */
class ImageDimensionViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Get width / height from image file
	 *
	 * @param string $dimension Either width or height
	 * @throws \TYPO3\CMS\Install\ViewHelpers\Exception
	 * @return integer width or height
	 */
	public function render($dimension = 'width') {
		if ($dimension !== 'width' && $dimension !== 'height') {
			throw new \TYPO3\CMS\Install\ViewHelpers\Exception(
				'Dimension must be either \'width\' or \'height\'',
				1369563247
			);
		}
		$absolutePathToFile = $this->renderChildren();
		if (!is_file($absolutePathToFile)) {
			throw new \TYPO3\CMS\Install\ViewHelpers\Exception(
				'File not found',
				1369563248
			);
		}
		$actualDimension = getimagesize($absolutePathToFile);
		if ($dimension === 'width') {
			$size = $actualDimension[0];
		} else {
			$size = $actualDimension[1];
		}
		return $size;
	}
}
