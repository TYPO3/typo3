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

namespace TYPO3\CMS\Core\Database\Schema;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff as DoctrineColumnDiff;

/**
 * Based on the doctrine/dbal implementation restoring direct property access.
 *
 * @internal not part of public Core API.
 */
class ColumnDiff extends DoctrineColumnDiff
{
    public function __construct(public Column $oldColumn, public Column $newColumn)
    {
        // NOTE: parent::__construct() not called by intention.
    }

    public function getOldColumn(): Column
    {
        return $this->oldColumn;
    }

    public function getNewColumn(): Column
    {
        return $this->newColumn;
    }

    public function hasTypeChanged(): bool
    {
        return $this->newColumn->getType()::class !== $this->oldColumn->getType()::class;
    }

    public function hasLengthChanged(): bool
    {
        return $this->hasPropertyChanged(static function (Column $column): ?int {
            return $column->getLength();
        });
    }

    public function hasPrecisionChanged(): bool
    {
        return $this->hasPropertyChanged(static function (Column $column): ?int {
            return $column->getPrecision();
        });
    }

    public function hasScaleChanged(): bool
    {
        return $this->hasPropertyChanged(static function (Column $column): int {
            return $column->getScale();
        });
    }

    public function hasUnsignedChanged(): bool
    {
        return $this->hasPropertyChanged(static function (Column $column): bool {
            return $column->getUnsigned();
        });
    }

    public function hasFixedChanged(): bool
    {
        return $this->hasPropertyChanged(static function (Column $column): bool {
            return $column->getFixed();
        });
    }

    public function hasNotNullChanged(): bool
    {
        return $this->hasPropertyChanged(static function (Column $column): bool {
            return $column->getNotnull();
        });
    }

    public function hasDefaultChanged(): bool
    {
        $oldDefault = $this->oldColumn->getDefault();
        $newDefault = $this->newColumn->getDefault();
        // Null values need to be checked additionally as they tell whether to create or drop a default value.
        // null != 0, null != false, null != '' etc. This affects platform's table alteration SQL generation.
        if (($newDefault === null) xor ($oldDefault === null)) {
            return true;
        }
        return $newDefault != $oldDefault;
    }

    public function hasAutoIncrementChanged(): bool
    {
        return $this->hasPropertyChanged(static function (Column $column): bool {
            return $column->getAutoincrement();
        });
    }

    public function hasCommentChanged(): bool
    {
        return $this->hasPropertyChanged(static function (Column $column): string {
            return $column->getComment();
        });
    }

    private function hasPropertyChanged(callable $property): bool
    {
        return $property($this->newColumn) !== $property($this->oldColumn);
    }
}
