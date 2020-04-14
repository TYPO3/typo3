<?php

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

namespace TYPO3\CMS\Frontend\ContentObject;

use TYPO3\CMS\Core\Resource\File;

/**
 * Interface for classes which hook into \TYPO3\CMS\Frontend\ContentObject and do additional getImgResource processing
 */
interface FileLinkHookInterface
{
    /**
     * Finds alternative previewImage for given File.
     *
     * @param File $file
     * @return File
     * @abstract
     */
    public function getPreviewImage(File $file);
}
