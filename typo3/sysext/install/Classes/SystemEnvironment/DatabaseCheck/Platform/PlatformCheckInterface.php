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

namespace TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck\Platform;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Install\SystemEnvironment\CheckInterface;

/**
 * @internal This interface is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
interface PlatformCheckInterface extends CheckInterface
{
    /**
     * Checks the character set of the database and reports an error if it is not utf-8.
     *
     * @param Connection $connection to the database to be checked
     */
    public function checkDefaultDatabaseCharset(Connection $connection): void;

    /**
     * Checks the character set of the database server and reports an info if it is not utf-8.
     *
     * @param Connection $connection to the database to be checked
     */
    public function checkDefaultDatabaseServerCharset(Connection $connection): void;

    public static function isValidDatabaseName(string $databaseName): bool;
}
