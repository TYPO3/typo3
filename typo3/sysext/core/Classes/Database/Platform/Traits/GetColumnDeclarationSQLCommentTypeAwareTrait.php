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

namespace TYPO3\CMS\Core\Database\Platform\Traits;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Types\JsonType;
use Doctrine\DBAL\Types\Type;

/**
 * This trait provides some methods to restore removed behaviour of Doctrine DBAL within the extended
 * {@see AbstractPlatform} hierarchy.
 *
 * Related code places code has been taken from or adopted for it:
 *
 * - https://github.com/doctrine/dbal/blob/61446f07fcb522414d6cfd8b1c3e5f9e18c579ba/src/Platforms/AbstractPlatform.php#L555-L572
 * - https://github.com/doctrine/dbal/blob/61446f07fcb522414d6cfd8b1c3e5f9e18c579ba/src/Platforms/AbstractPlatform.php#L574-L597
 * - https://github.com/doctrine/dbal/blob/61446f07fcb522414d6cfd8b1c3e5f9e18c579ba/src/Types/Type.php#L275-L295
 * - https://github.com/doctrine/dbal/blob/61446f07fcb522414d6cfd8b1c3e5f9e18c579ba/src/Types/JsonType.php#L80-L95
 */
trait GetColumnDeclarationSQLCommentTypeAwareTrait
{
    /**
     * Note that this provides a method override to combine type based comments with the column comment on platforms.
     *
     * Obtains DBMS specific SQL code portion needed to declare a generic type
     * column to be used in statements like CREATE TABLE.
     *
     * @internal The method should be only used from within the {@see AbstractPlatform} class hierarchy.
     *
     * @param string  $name   The name the column to be declared.
     * @param mixed[] $column An associative array with the name of the properties
     *                        of the column being declared as array indexes. Currently, the types
     *                        of supported column properties are as follows:
     *
     *      length
     *          Integer value that determines the maximum length of the text
     *          column. If this argument is missing the column should be
     *          declared to have the longest length allowed by the DBMS.
     *      default
     *          Text value to be used as default for this column.
     *      notnull
     *          Boolean flag that indicates whether this column is constrained
     *          to not be set to null.
     *      charset
     *          Text value with the default CHARACTER SET for this column.
     *      collation
     *          Text value with the default COLLATION for this column.
     *      columnDefinition
     *          a string that defines the complete column
     *
     * @return string DBMS specific SQL code portion that should be used to declare the column.
     */
    public function getColumnDeclarationSQL(string $name, array $column): string
    {
        return parent::getColumnDeclarationSQL($name, $this->addTypeCommentIfNeeded($column));
    }

    /**
     * Add type comment (`DC2Type:<TypeName>`) to column comment if required.
     *
     * Adopted from Doctrine DBAL 3.9:
     * - https://github.com/doctrine/dbal/blob/61446f07fcb522414d6cfd8b1c3e5f9e18c579ba/src/Platforms/AbstractPlatform.php#L555-L572
     *   returning comment addition for column comment.
     * - https://github.com/doctrine/dbal/blob/61446f07fcb522414d6cfd8b1c3e5f9e18c579ba/src/Platforms/AbstractPlatform.php#L574-L597
     *   adding the type comment to column comment, if overall type comments has not been disabled (not the case for TYPO3)
     *   and the column type method `requiresSQLCommentHint()` returned true for that type and platform, which no longer
     *   exits and are now simplified processed with {@see self::typeRequiresCommentHint()}.
     */
    private function addTypeCommentIfNeeded(array $column): array
    {
        /** @var AbstractPlatform $self Needed to satisfy PHPStan */
        $self = $this;
        if ($this->typeRequiresCommentHint($self, $column['type'])) {
            $column['comment'] .= '(DC2Type:' . Type::lookupName($column['type']) . ')';
        }
        return $column;
    }

    /**
     * Platform specific type selection requiring column comment type specification.
     *
     * Up to Doctrine DBAL v3, this has been handled throughout various places, for example using the removed
     * `requiresSQLCommentHint()` method on DoctrineType implementations.
     * https://github.com/doctrine/dbal/blob/61446f07fcb522414d6cfd8b1c3e5f9e18c579ba/src/Types/Type.php#L275-L295 for
     * the basic implementation, where each type could override that method.
     *
     * Instead of extending all types to restore that behaviour, a mapping logic is now added with that method,
     * for example:
     * - https://github.com/doctrine/dbal/blob/61446f07fcb522414d6cfd8b1c3e5f9e18c579ba/src/Types/JsonType.php#L80-L95
     */
    private function typeRequiresCommentHint(AbstractPlatform $platform, Type $type): bool
    {
        $map = [
            SQLitePlatform::class => [
                JsonType::class,
            ],
            AbstractPlatform::class => [],
        ];
        foreach ($map as $platformClass => $platformTypes) {
            if (!$platform instanceof $platformClass) {
                continue;
            }
            foreach ($platformTypes as $platformType) {
                if ($type instanceof $platformType) {
                    return true;
                }
            }
        }
        return false;
    }
}
