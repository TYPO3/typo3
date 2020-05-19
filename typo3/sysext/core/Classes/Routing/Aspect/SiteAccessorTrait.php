<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Routing\Aspect;

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
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Helper trait to use a site within a class.
 *
 * @internal this is not public API yet as this might change, and could be changed within TYPO3 Core at any time.
 */
trait SiteAccessorTrait
{
    /**
     * @var Site
     */
    protected $site;

    /**
     * @var SiteMatcher|null
     */
    protected $siteMatcher;

    /**
     * @param Site $site
     */
    public function setSite(Site $site): void
    {
        $this->site = $site;
    }

    /**
     * @return Site
     */
    public function getSite(): Site
    {
        return $this->site;
    }

    /**
     * Filters records that are contained in current site
     * (resolved from current SiteLanguage).
     *
     * Results keep original indexes and probably needs to
     * be passed through `array_values` for e.g. using the
     * first result by `$results[0]`.
     *
     * @param array $results
     * @return array
     */
    protected function filterContainedInSite(array $results): array
    {
        if (empty($results)) {
            return $results;
        }
        return array_filter(
            $results,
            function (array $result) {
                // default FrontendRestrictionContainer retrieves only live records
                // (no specific workspace & move-placeholder resolving required here)
                $pageId = (int)$result['pid'];
                return $this->isPageIdContainedInSite($pageId);
            }
        );
    }

    /**
     * Determines whether page is contained in current site
     * (resolved from current SiteLanguage).
     *
     * @param int $pageId
     * @return bool
     */
    protected function isPageIdContainedInSite(int $pageId): bool
    {
        try {
            $expectedSite = $this->getSiteMatcher()->matchByPageId($pageId);
            return $expectedSite->getRootPageId() === $this->site->getRootPageId();
        } catch (SiteNotFoundException $exception) {
            // Same as in \TYPO3\CMS\Core\DataHandling\SlugHelper::isUniqueInSite
            // where it is assumed that a record, that is not in site context,
            // but still configured uniqueInSite is unique. We therefore must assume
            // the resolved record to be rightfully part of the current site.
            return true;
        }
    }

    protected function getSiteMatcher(): SiteMatcher
    {
        if (!isset($this->siteMatcher)) {
            $this->siteMatcher = GeneralUtility::makeInstance(SiteMatcher::class);
        }
        return $this->siteMatcher;
    }
}
