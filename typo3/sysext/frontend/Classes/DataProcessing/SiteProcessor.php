<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\DataProcessing;

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

use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Fetch the site object containing all information about the current site
 *
 * Example TypoScript configuration:
 *
 * 10 = TYPO3\CMS\Frontend\DataProcessing\SiteProcessor
 * 10 {
 *   as = site
 * }
 *
 * where "as" names the variable containing the site object
 */
class SiteProcessor implements DataProcessorInterface
{

    /**
     * @param ContentObjectRenderer $cObj The data of the content element or page
     * @param array $contentObjectConfiguration The configuration of Content Object
     * @param array $processorConfiguration The configuration of this processor
     * @param array $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
     * @return array the processed data as key/value store
     */
    public function process(ContentObjectRenderer $cObj, array $contentObjectConfiguration, array $processorConfiguration, array $processedData): array
    {
        $targetVariableName = $cObj->stdWrapValue('as', $processorConfiguration, 'site');
        $processedData[$targetVariableName] = $this->getCurrentSite();
        return $processedData;
    }

    /**
     * Returns the currently configured "site" if a site is configured (= resolved) in the current request.
     *
     * @return SiteInterface|null
     */
    protected function getCurrentSite(): ?SiteInterface
    {
        try {
            return $this->getMatcher()->matchByPageId($this->getCurrentPageId());
        } catch (SiteNotFoundException $e) {
            // Do nothing
        }

        return null;
    }

    /**
     * @return SiteMatcher
     */
    protected function getMatcher(): SiteMatcher
    {
        return GeneralUtility::makeInstance(SiteMatcher::class);
    }

    /**
     * @return int
     */
    protected function getCurrentPageId(): int
    {
        return (int)$GLOBALS['TSFE']->id;
    }
}
