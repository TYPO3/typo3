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

namespace TYPO3\CMS\Core\Database\Platform;

use Doctrine\DBAL\Platforms\MariaDBPlatform as DoctrineMariaDBPlatform;
use Doctrine\DBAL\Schema\TableDiff as DoctrineTableDiff;
use TYPO3\CMS\Core\Database\Platform\Traits\GetColumnDeclarationSQLCommentTypeAwareTrait;
use TYPO3\CMS\Core\Database\Platform\Traits\MySQLCompatibleAlterTablePlatformAwareTrait;
use TYPO3\CMS\Core\Database\Schema\TableDiff;

/**
 * doctrine/dbal 4+ removed the old doctrine event system. The new way is to extend the platform
 * class(es) and directly override the methods instead of consuming events. Therefore, we need to
 * extend the platform classes to provide some changes for TYPO3 database schema operations.
 *
 * Note: `doctrine/dbal 4` raised minimal supported MariaDB version to 10.4.3 which the MariaDBPlatform reflects now.
 *
 * @internal not part of Public Core API.
 */
class MariaDBPlatform extends DoctrineMariaDBPlatform
{
    use GetColumnDeclarationSQLCommentTypeAwareTrait;
    use MySQLCompatibleAlterTablePlatformAwareTrait;

    /**
     * Gets the SQL statements for altering an existing table.
     *
     * This method returns an array of SQL statements, since some platforms need several statements.
     *
     * @return list<string>
     */
    public function getAlterTableSQL(TableDiff|DoctrineTableDiff $diff): array
    {
        return $this->getCustomAlterTableSQLEngineOptions($this, $diff, parent::getAlterTableSQL($diff));
    }
}
