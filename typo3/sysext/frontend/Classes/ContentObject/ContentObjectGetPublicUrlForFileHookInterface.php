<?php
namespace TYPO3\CMS\Frontend\ContentObject;

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
 * Interface for hooks to fetch the public URL of files
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
interface ContentObjectGetPublicUrlForFileHookInterface {
	/**
	 * Post-processes a public URL.
	 *
	 * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $parent The current content object (context)
	 * @param array $configuration TypoScript configuration
	 * @param \TYPO3\CMS\Core\Resource\File $file The file object to be used
	 * @param string $pubicUrl Reference to the public URL
	 */
	public function postProcess(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $parent, array $configuration, \TYPO3\CMS\Core\Resource\File $file, &$pubicUrl);

}
