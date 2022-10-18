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

namespace TYPO3\CMS\Redirects\EventListener;

use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Redirects\Event\RedirectWasHitEvent;

/**
 * Event listener to increment a matched redirect records' hit count
 */
final class IncrementHitCount
{
    protected Features $features;

    public function __construct(Features $features)
    {
        $this->features = $features;
    }

    public function __invoke(RedirectWasHitEvent $event): void
    {
        $matchedRedirect = $event->getMatchedRedirect();
        if ($matchedRedirect['disable_hitcount']
            || !$this->features->isFeatureEnabled('redirects.hitCount')
        ) {
            // Early return in case hit count is disabled
            return;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_redirect');
        $queryBuilder
            ->update('sys_redirect')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($matchedRedirect['uid'], Connection::PARAM_INT))
            )
            ->set('hitcount', $queryBuilder->quoteIdentifier('hitcount') . '+1', false)
            ->set('lasthiton', $GLOBALS['EXEC_TIME'])
            ->executeStatement();
    }
}
