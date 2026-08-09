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

namespace TYPO3\CMS\Core\Database\Schema\Exception;

/**
 * Thrown when a table definition indexes a column that none of the definitions for that table
 * declares - typically a typo, or a column that was removed while the index over it was kept.
 *
 * Extends StatementException so that consumers reporting invalid table definitions to the user,
 * such as the install tool, keep doing so for this case.
 *
 * @internal not part of public core API.
 */
final class InvalidIndexDefinitionException extends StatementException
{
    public static function forUnknownColumn(string $tableName, string $indexName, string $columnName): self
    {
        return new self(
            sprintf(
                '[Semantic Error] Index "%s" of table "%s" is defined over column "%s", which the table does not have.',
                $indexName,
                $tableName,
                $columnName
            ),
            1786292528
        );
    }
}
