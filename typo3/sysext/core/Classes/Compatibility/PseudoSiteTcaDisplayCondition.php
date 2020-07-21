<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Compatibility;

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

use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\Site\Entity\PseudoSite;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A display condition that returns true if the page we are dealing
 * with is in a page tree that is represented by a PseudoSite object.
 *
 * This is used to suppress the 'slug' field in pseudo site page trees
 * when editing page records and to show the alias field.
 *
 * Both "Pseudo sites" and "alias" db field will bite the dust in TYPO3 v10.0,
 * so this is a temporary display condition for v9 only and thus marked internal.
 *
 * @internal Implementation and class will probably vanish in TYPO3 v10.0 without further notice
 */
class PseudoSiteTcaDisplayCondition
{
    /**
     * Takes the given page id of the record and verifies if the page has
     * a pseudo site object or a site object attached.
     *
     * @param array $parameters
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isInPseudoSite(array $parameters): bool
    {
        if (!isset($parameters['conditionParameters'][0])
            || $parameters['conditionParameters'][0] !== 'pages'
            || !isset($parameters['conditionParameters'][1])
            || (!in_array($parameters['conditionParameters'][1], ['true', 'false'], true))
            || empty($parameters['record']['uid'])
        ) {
            // Validate arguments
            throw new \InvalidArgumentException(
                'Invalid arguments using isInPseudoSite display condition',
                1535055415
            );
        }

        // uid is set if we're editing an existing page
        // This resolves to 0 if the page is 'new'
        $defaultLanguagePageId = (int)($parameters['record']['t3ver_oid'] ?: $parameters['record']['uid']);
        if (is_array($parameters['record']['l10n_parent'])
            && (int)$parameters['record']['sys_language_uid'][0] > 0
        ) {
            // But if the page is a localized page, we take the l10n_parent as uid for the sitematcher
            $defaultLanguagePageId = (int)$parameters['record']['l10n_parent'][0];
        }
        // If still 0, this is probably a 'new' page somewhere, so we take the pid
        // For additional fun, pid can be -1*real-pid, if the new page is created "after" an existing page
        // Also take care if a record is a localized one, so set the pid instead of the parent (language) record
        if ($defaultLanguagePageId === 0
            || (int)$parameters['record']['sys_language_uid'][0] > 0
        ) {
            $defaultLanguagePageId = abs((int)$parameters['record']['pid']);
        }
        // And if now still 0, this is a 'new' page below pid 0. This will resolve to a 'NullSite' object

        // If not a Site or a NullSite object, it must be a PseudoSite. We show the slug for
        // NullSites (new pages below root) to simplify the editing workflow a bit.
        $site = GeneralUtility::makeInstance(SiteMatcher::class)->matchByPageId($defaultLanguagePageId);
        $isInPseudoSite = ($site instanceof PseudoSite);

        if ($parameters['conditionParameters'][1] === 'false') {
            // Negate if requested
            return !$isInPseudoSite;
        }

        return $isInPseudoSite;
    }
}
