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

/**
 * Interface for classes which hook into \TYPO3\CMS\Frontend\ContentObject and do additional getImgResource processing
 */
interface ContentObjectGetImageResourceHookInterface
{
    /**
     * Hook for post-processing image resources
     *
     * @param string $file Original image file
     * @param array $configuration TypoScript getImgResource properties
     * @param array $imageResource Information of the created/converted image resource
     * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $parent Parent content object
     * @return array Modified image resource information
     */
    public function getImgResourcePostProcess($file, array $configuration, array $imageResource, ContentObjectRenderer $parent);
}
