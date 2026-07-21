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

namespace TYPO3\CMS\Core\Tests\Unit\Database\Schema;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Schema\TableDiff;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TableDiffTest extends UnitTestCase
{
    #[Test]
    public function getDroppedForeignKeyConstraintNamesReturnsEmptyArrayWithoutDroppedForeignKeys(): void
    {
        $subject = new TableDiff(new Table('a_test_table'));
        self::assertSame([], $subject->getDroppedForeignKeyConstraintNames());
    }

    #[Test]
    public function getDroppedForeignKeyConstraintNamesReturnsNamesOfDroppedForeignKeys(): void
    {
        $subject = new TableDiff(
            oldTable: new Table('a_test_table'),
            droppedForeignKeys: [
                new ForeignKeyConstraint(['a_field'], 'another_test_table', ['uid'], 'fk_a_test_table_a_field'),
            ],
        );

        $names = $subject->getDroppedForeignKeyConstraintNames();

        self::assertCount(1, $names);
        self::assertSame('fk_a_test_table_a_field', $names[0]->toString());
    }
}
