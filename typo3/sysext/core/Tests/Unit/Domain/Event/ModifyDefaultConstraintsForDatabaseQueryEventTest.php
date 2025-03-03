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

namespace TYPO3\CMS\Core\Tests\Unit\Domain\Event;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Domain\Event\ModifyDefaultConstraintsForDatabaseQueryEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ModifyDefaultConstraintsForDatabaseQueryEventTest extends UnitTestCase
{
    #[Test]
    public function gettersReturnInitializedObjects(): void
    {
        $table = 'a_table';
        $tableAlias = 'table_alias';
        $expressionBuilder = new ExpressionBuilder($this->createMock(Connection::class));
        $constraints = ['foo' => new CompositeExpression('foo')];
        $enableFieldsToIgnore = ['a_field' => true];
        $context = new Context();

        $event = new ModifyDefaultConstraintsForDatabaseQueryEvent(
            $table,
            $tableAlias,
            $expressionBuilder,
            $constraints,
            $enableFieldsToIgnore,
            $context
        );

        self::assertEquals($table, $event->getTable());
        self::assertEquals($tableAlias, $event->getTableAlias());
        self::assertEquals($expressionBuilder, $event->getExpressionBuilder());
        self::assertEquals($constraints, $event->getConstraints());
        self::assertEquals(array_keys($enableFieldsToIgnore), $event->getEnableFieldsToIgnore());
        self::assertEquals($context, $event->getContext());
    }

    #[Test]
    public function setConstraintsAllowsModifiyingDefaultConstraints(): void
    {
        $event = new ModifyDefaultConstraintsForDatabaseQueryEvent(
            '',
            '',
            new ExpressionBuilder($this->createMock(Connection::class)),
            [],
            [],
            new Context()
        );

        self::assertEmpty($event->getConstraints());

        $constraints = [new CompositeExpression('foo')];
        $event->setConstraints($constraints);
        self::assertEquals($constraints, $event->getConstraints());

        // unset constraints again
        $event->setConstraints([]);
        self::assertEmpty($event->getConstraints());
    }
}
