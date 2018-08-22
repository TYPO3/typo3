<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Routing\SiteMatcher;
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
     * Find and add site object
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result): array
    {
        $pageIdDefaultLanguage = (int)($result['defaultLanguagePageRow']['uid'] ?? $result['effectivePid']);
        $result['site'] = GeneralUtility::makeInstance(SiteMatcher::class)->matchByPageId($pageIdDefaultLanguage);
        return $result;
    }
}
