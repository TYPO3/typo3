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
 * Interface for hooks to fetch the public URL of files
 */
interface ContentObjectGetPublicUrlForFileHookInterface
{
    /**
     * Post-processes a public URL.
     *
     * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $parent The current content object (context)
     * @param array $configuration TypoScript configuration
     * @param File $file The file object to be used
     * @param string $pubicUrl Reference to the public URL
     */
    public function postProcess(ContentObjectRenderer $parent, array $configuration, File $file, &$pubicUrl);
}
