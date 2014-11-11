<?php
namespace TYPO3\CMS\Extensionmanager\ViewHelpers;

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
 * Resizes a given image (if required) and renders the respective img tag
 * In general just calls the parent image view helper but catches
 * the "file does not exist" exception thrown by the file abstraction layer
 *
 * = Examples =
 *
 * <code title="Default">
 * <f:image src="EXT:myext/Resources/Public/typo3_logo.png" alt="alt text" />
 * </code>
 * <output>
 * <img alt="alt text" src="typo3conf/ext/myext/Resources/Public/typo3_logo.png" width="396" height="375" />
 * or (in BE mode):
 * <img alt="alt text" src="../typo3conf/ext/viewhelpertest/Resources/Public/typo3_logo.png" width="396" height="375" />
 * </output>
 *
 * <code title="Inline notation">
 * {f:image(src: 'EXT:viewhelpertest/Resources/Public/typo3_logo.png', alt: 'alt text', minWidth: 30, maxWidth: 40)}
 * </code>
 * <output>
 * <img alt="alt text" src="../typo3temp/pics/f13d79a526.png" width="40" height="38" />
 * (depending on your TYPO3s encryption key)
 * </output>
 *
 * <code title="non existing image">
 * <f:image src="NonExistingImage.png" alt="foo" />
 * </code>
 * <output>
 * Could not get image resource for "NonExistingImage.png".
 * </output>
 *
 * @internal
 */
class ImageViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\ImageViewHelper {

	/**
	 * Resizes a given image (if required) and renders the respective img tag
	 *
	 * @param string $src
	 * @param string $width width of the image. This can be a numeric value representing the fixed width of the image in pixels. But you can also perform simple calculations by adding "m" or "c" to the value. See imgResource.width for possible options.
	 * @param string $height height of the image. This can be a numeric value representing the fixed height of the image in pixels. But you can also perform simple calculations by adding "m" or "c" to the value. See imgResource.width for possible options.
	 * @param int $minWidth minimum width of the image
	 * @param int $minHeight minimum height of the image
	 * @param int $maxWidth maximum width of the image
	 * @param int $maxHeight maximum height of the image
	 * @param string $fallbackImage an optional fallback image if the $src image cannot be loaded
	 * @return string rendered tag.
	 */
	public function render($src, $width = NULL, $height = NULL, $minWidth = NULL, $minHeight = NULL, $maxWidth = NULL, $maxHeight = NULL, $fallbackImage = '') {
		$image = '';
		try {
			$image = parent::render($src, $width, $height, $minWidth, $minHeight, $maxWidth, $maxHeight);
		} catch (\Exception $e) {
			if ($fallbackImage !== '') {
				$image = static::render($fallbackImage, $width, $height, $minWidth, $minHeight, $maxWidth, $maxHeight);
			}
			/** @var \TYPO3\CMS\Core\Log\Logger $logger */
			$logger = $this->objectManager->get('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger(__CLASS__);
			$logger->log(\TYPO3\CMS\Core\Log\LogLevel::WARNING, $e->getMessage());
		}
		return $image;
	}

}
