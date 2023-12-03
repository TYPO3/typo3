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

use Doctrine\DBAL\Platforms\AbstractPlatform as DoctrineAbstractPlatform;
use Doctrine\DBAL\Schema\SchemaDiff;

/**
 * This trait is used to provide the `getSaveAlterSchemaSQL()` method as a replacement for the
 * replacement-less deprecated `SchemaDiff->toSaveSql()` with Doctrine DBAL v3 and used as
 * intermediate solution until a refactoring can be archived to provide this in a other way.
 *
 * @internal for use in `ext:core` and not part of public core API.
 */
trait PlatformSaveAlterSchemaSQLTrait
{
    /**
     * Generates SQL statements that can be used to apply the diff.
     *
     * @return list<string>
     */
    public function getSaveAlterSchemaSQL(SchemaDiff $diff): array
    {
        if (!($this instanceof DoctrineAbstractPlatform)) {
            throw new \RuntimeException(
                sprintf(
                    'Trait "%s" can only be used in subclasses of "%s". "%s" is not a subclass.',
                    PlatformSaveAlterSchemaSQLTrait::class,
                    DoctrineAbstractPlatform::class,
                    static::class
                ),
                1701612351
            );
        }

        // @todo Replace `$diff->toSaveSql()` with adopted code from `AbstractPlatform->getAlterSchemaSQL()` reflecting
        //       the save variant when upgraded to `Doctrine DBAL v4`.

        return $diff->toSaveSql($this);
    }
}
