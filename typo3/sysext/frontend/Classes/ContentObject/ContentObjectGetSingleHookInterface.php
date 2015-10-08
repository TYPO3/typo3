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
 * Interface for classes which hook into \TYPO3\CMS\Frontend\ContentObject and do additional cObjGetSingle processing
 */
interface ContentObjectGetSingleHookInterface
{
    /**
     * Renders content objects, that are not defined in the core
     *
     * @param string $contentObjectName The content object name, eg. "TEXT" or "USER" or "IMAGE
     * @param array $configuration Array with TypoScript properties for the content object
     * @param string $TypoScriptKey Label used for the internal debug tracking
     * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $parentObject Parent content object
     * @return string cObject output
     */
    public function getSingleContentObject($contentObjectName, array $configuration, $TypoScriptKey, ContentObjectRenderer &$parentObject);
}
