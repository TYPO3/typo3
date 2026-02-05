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

namespace TYPO3\CMS\Redirects\Service;

use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal Only to be used within TYPO3. Might change in the future.
 */
class ShortUrlService
{
    private const TABLE = 'sys_redirect';
    private const CHARACTER_SET = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
    private const PATH_LENGTH = 8;
    private const MAX_RETRIES = 10;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly Random $random,
    ) {}

    public function generateUniqueShortUrlPath(string $sourceHost): ?string
    {
        $charSetLength = mb_strlen(self::CHARACTER_SET);
        for ($attempt = 0; $attempt < self::MAX_RETRIES; $attempt++) {
            $path = '/';
            for ($i = 0; $i < self::PATH_LENGTH; $i++) {
                $path .= self::CHARACTER_SET[$this->random->generateRandomInteger(0, $charSetLength - 1)];
            }
            if ($this->isUniqueShortUrl($sourceHost, $path)) {
                return $path;
            }
        }
        return null;
    }

    public function isUniqueShortUrl(string $sourceHost, string $sourcePath): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll()->add(
            GeneralUtility::makeInstance(DeletedRestriction::class)
        );
        $count = $queryBuilder
            ->count('uid')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('source_host', $queryBuilder->createNamedParameter($sourceHost)),
                    $queryBuilder->expr()->eq('source_path', $queryBuilder->createNamedParameter($sourcePath))
                )
            )
            ->executeQuery()
            ->fetchOne();

        return $count === 0;
    }
}
