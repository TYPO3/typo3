<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Linkvalidator\Repository;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for finding broken links that were detected previously.
 */
class BrokenLinkRepository
{
    /**
     * Check if linkTarget is in list of broken links.
     *
     * @param string $linkTarget Url to check for. Can be a URL (for external links)
     *   a page uid (for db links), a file reference (for file links), etc.
     * @return int the amount of usages this broken link is used in this installation
     */
    public function getNumberOfBrokenLinks(string $linkTarget): int
    {
        try {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_linkvalidator_link');
            $queryBuilder
                ->count('uid')
                ->from('tx_linkvalidator_link')
                ->where(
                    $queryBuilder->expr()->eq('url', $queryBuilder->createNamedParameter($linkTarget))
                );
            return (int)$queryBuilder
                    ->execute()
                    ->fetchColumn(0);
        } catch (\Doctrine\DBAL\Exception\TableNotFoundException $e) {
            return 0;
        }
    }
}
