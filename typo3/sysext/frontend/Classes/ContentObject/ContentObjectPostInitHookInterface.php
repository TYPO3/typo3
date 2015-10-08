<?php
namespace TYPO3\CMS\Frontend\ContentObject;

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

/**
 * Interface for classes which hook into \TYPO3\CMS\Frontend\ContentObject and do additional
 * initialization processing.
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
