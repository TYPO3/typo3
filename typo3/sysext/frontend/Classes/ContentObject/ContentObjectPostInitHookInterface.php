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
 * Interface for classes which hook into \TYPO3\CMS\Frontend\ContentObject and do additional
 * initialization processing.
 *
 * @deprecated not in use anymore since TYPO3 v13, will be removed in TYPO3 14.
 *             Only stays to allow extensions to be compatible with TYPO3 v12 + v13.
 */
interface ContentObjectPostInitHookInterface
{
    /**
     * Hook for post processing the initialization of ContentObjectRenderer
     *
     * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $parentObject Parent content object
     */
    public function postProcessContentObjectInitialization(ContentObjectRenderer &$parentObject);
}
