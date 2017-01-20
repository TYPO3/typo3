<?php
namespace TYPO3\CMS\Install\SystemEnvironment;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\SystemEnvironment\DatabasePlatform\MySqlCheck;
use TYPO3\CMS\Install\SystemEnvironment\DatabasePlatform\PostgreSqlCheck;

/**
 * Check database configuration status
 *
 * This class is a hardcoded requirement check for the database server.
 *
 * The status messages and title *must not* include HTML, use plain
 * text only. The return values of this class are not bound to HTML
 * and can be used in different scopes (eg. as json array).
 */
class DatabaseCheck implements CheckInterface
{
    /**
     * List of database platforms to check
     *
     * @var array
     */
    protected $databasePlatformChecks = [
        MySqlCheck::class,
        PostgreSqlCheck::class,
    ];

    /**
     * Get status of each database platform defined in the list
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    public function getStatus(): array
    {
        $databaseStatus = [];

        foreach ($this->databasePlatformChecks as $databasePlatformCheckClass) {
            $databaseStatus += GeneralUtility::makeInstance($databasePlatformCheckClass)->getStatus();
        }
        return $databaseStatus;
    }
}
