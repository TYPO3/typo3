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
 * Interface for classes which hook into getSourceCollection for additional processing
 */
interface ContentObjectOneSourceCollectionHookInterface
{
    /**
     * Renders One Source Collection
     *
     * @param array $sourceRenderConfiguration Array with TypoScript Properties for the imgResource
     * @param array $sourceConfiguration
     * @param string $oneSourceCollection already prerendered SourceCollection
     * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $parentObject Parent content object
     * @internal param array $configuration Array with the Source Configuration
     * @return string HTML Content for oneSourceCollection
     */
    public function getOneSourceCollection(array $sourceRenderConfiguration, array $sourceConfiguration, $oneSourceCollection, ContentObjectRenderer &$parentObject);
}
