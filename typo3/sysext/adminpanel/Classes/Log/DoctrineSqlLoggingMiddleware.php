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

namespace TYPO3\CMS\Adminpanel\Log;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware as MiddlewareInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Wrapper around the Doctrine SQL Driver for logging sql queries
 *
 * @internal
 */
class DoctrineSqlLoggingMiddleware implements MiddlewareInterface
{
    protected DoctrineSqlLogger $logger;

    public function wrap(DriverInterface $driver): DriverInterface
    {
        $this->logger = GeneralUtility::makeInstance(DoctrineSqlLogger::class);
        return new LoggingDriver($driver, $this->logger);
    }

    public function enable(): void
    {
        $this->logger->enable();
    }

    public function getQueries(): array
    {
        return $this->logger->getQueries();
    }
}
