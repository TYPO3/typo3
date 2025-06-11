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

namespace TYPO3\CMS\Core\Tests\Functional\Database\Query\Restriction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Query\Restriction\PageIdListRestriction;

final class PageIdListRestrictionTest extends AbstractRestrictionTestCase
{
    #[Test]
    public function buildRestrictionsAddsPidWhereClause(): void
    {
        $subject = new PageIdListRestriction([], [0]);
        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        self::assertSame('"aTable"."pid" IN (0)', (string)$expression);
    }

    #[Test]
    public function buildRestrictionsAddsAliasedPidWhereClause(): void
    {
        $subject = new PageIdListRestriction([], [13, 14]);
        $expression = $subject->buildExpression(['aTableAlias' => 'aTable'], $this->expressionBuilder);
        self::assertSame('"aTableAlias"."pid" IN (13, 14)', (string)$expression);
    }

    #[Test]
    public function buildRestrictionsAddsPidWhereClauseIfTableIsSpecified(): void
    {
        $subject = new PageIdListRestriction(['aTable'], [13, 14]);
        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        self::assertSame('"aTable"."pid" IN (13, 14)', (string)$expression);
    }

    #[Test]
    public function buildRestrictionsAddsAliasedPidWhereClauseIfAliasIsSpecified(): void
    {
        $subject = new PageIdListRestriction(['aTableAlias'], [13, 14]);
        $expression = $subject->buildExpression(['aTableAlias' => 'aTable'], $this->expressionBuilder);
        self::assertSame('"aTableAlias"."pid" IN (13, 14)', (string)$expression);
    }

    #[Test]
    public function buildRestrictionsConvertsToIntegers(): void
    {
        $subject = new PageIdListRestriction(['aTableAlias'], ['13', '14']);
        $expression = $subject->buildExpression(['aTableAlias' => 'aTable'], $this->expressionBuilder);
        self::assertSame('"aTableAlias"."pid" IN (13, 14)', (string)$expression);
    }

    #[Test]
    public function buildRestrictionsSkipsUnrestrictedTablesIfOtherTableIsSpecifiedThanUsedInTheQuery(): void
    {
        $subject = new PageIdListRestriction(['aTable'], [13, 14]);
        $expression = $subject->buildExpression(['anotherTable' => 'anotherTable'], $this->expressionBuilder);
        self::assertSame('', (string)$expression);
    }
}
