<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Backend\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This data provider is used in casual edit record / new record / edit page / new page
 * scenarios: It find the site object for a page and adds it as 'site' in $result.
 *
 * Note this data provider has a loose dependency to DatabaseDefaultLanguagePageRow,
 * it needs that to determine the correct base pid if localized pages are edited.
 */
class SiteResolving implements FormDataProviderInterface
{
    /**
     * @var SiteFinder
     */
    protected $siteFinder;

    public function __construct(SiteFinder $siteFinder = null)
    {
        $this->siteFinder = $siteFinder ?? GeneralUtility::makeInstance(SiteFinder::class);
    }

    /**
     * Find and add site object
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result): array
    {
        if ($result['defaultLanguagePageRow']['t3ver_oid'] ?? null) {
            $pageIdDefaultLanguage = (int)$result['defaultLanguagePageRow']['t3ver_oid'];
        } elseif ($result['defaultLanguagePageRow']['uid'] ?? null) {
            $pageIdDefaultLanguage = (int)$result['defaultLanguagePageRow']['uid'];
        } elseif (array_key_exists('tableName', $result) && $result['tableName'] === 'pages') {
            if (!empty($result['databaseRow']['t3ver_oid'])) {
                $pageIdDefaultLanguage = $result['databaseRow']['t3ver_oid'];
            } else {
                $pageIdDefaultLanguage = $result['databaseRow']['uid'] ?? $result['effectivePid'];
            }
        } else {
            $pageIdDefaultLanguage = $result['effectivePid'];
        }
        $result['site'] = $this->resolveSite((int)$pageIdDefaultLanguage);
        return $result;
    }

    /**
     * @param int $pageId
     * @return SiteInterface
     */
    protected function resolveSite(int $pageId): SiteInterface
    {
        try {
            return $this->siteFinder->getSiteByPageId($pageId);
        } catch (SiteNotFoundException $e) {
            return new NullSite();
        }
    }
}
