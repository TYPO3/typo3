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
 * Interface for classes which hook into tslib_content and do additional getImgResource processing
 *
 * @author Oliver Hader <oliver@typo3.org>
 */
interface FileLinkHookInterface {
	/**
	 * Finds alternative previewImage for given File.
	 *
	 * @param \TYPO3\CMS\Core\Resource\File $file
	 * @return \TYPO3\CMS\Core\Resource\File
	 * @abstract
	 * @todo Define visibility
	 */
	public function getPreviewImage(\TYPO3\CMS\Core\Resource\File $file);

}
