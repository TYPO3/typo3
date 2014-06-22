<?php
namespace TYPO3\CMS\Install\ViewHelpers\Format;

/**
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
 * Simplified crop view helper that does not need a frontend environment
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <f:format.crop maxCharacters="10">This is some very long text</f:format.crop>
 * </code>
 * <output>
 * This is...
 * </output>
 *
 * <code title="Inline notation">
 * {someLongText -> f:format.crop(maxCharacters: 10)}
 * </code>
 * <output>
 * someLongText cropped after 10 characters...
 * (depending on the value of {someLongText})
 * </output>
 */
class CropViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Render the cropped text
	 *
	 * @param integer $maxCharacters Place where to truncate the string
	 * @throws \TYPO3\CMS\Install\ViewHelpers\Exception
	 * @return string cropped text
	 */
	public function render($maxCharacters) {
		if (empty($maxCharacters) || $maxCharacters < 1) {
			throw new \TYPO3\CMS\Install\ViewHelpers\Exception(
				'maxCharacters must be a positive integer',
				1371410113
			);
		}
		$stringToTruncate = $this->renderChildren();
		return substr($stringToTruncate, 0, $maxCharacters);
	}
}
