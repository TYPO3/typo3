<?php
declare (strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Unit\Database\Query\Restriction;

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

use TYPO3\CMS\Core\Database\Query\Restriction\RootLevelRestriction;

class RootLevelRestrictionTest extends AbstractRestrictionTestCase
{
    /**
     * @test
     */
    public function buildRestrictionsAddsPidWhereClause()
    {
        $subject = new RootLevelRestriction();
        $expression = $subject->buildExpression(['aTable' => ''], $this->expressionBuilder);
        $this->assertSame('"aTable"."pid" = 0', (string)$expression);
    }

    /**
     * @test
     */
    public function buildRestrictionsAddsAliasedPidWhereClause()
    {
        $subject = new RootLevelRestriction();
        $expression = $subject->buildExpression(['aTable' => 'aTableAlias'], $this->expressionBuilder);
        $this->assertSame('"aTableAlias"."pid" = 0', (string)$expression);
    }

    /**
     * @test
     */
    public function buildRestrictionsAddsPidWhereClauseIfTableIsSpecified()
    {
        $subject = new RootLevelRestriction(['aTable']);
        $expression = $subject->buildExpression(['aTable' => ''], $this->expressionBuilder);
        $this->assertSame('"aTable"."pid" = 0', (string)$expression);
    }

    /**
     * @test
     */
    public function buildRestrictionsAddsAliasedPidWhereClauseIfAliasIsSpecified()
    {
        $subject = new RootLevelRestriction(['aTableAlias']);
        $expression = $subject->buildExpression(['aTable' => 'aTableAlias'], $this->expressionBuilder);
        $this->assertSame('"aTableAlias"."pid" = 0', (string)$expression);
    }

    /**
     * @test
     */
    public function buildRestrictionsSkipsUnrestrictedTablesIfOtherTableIsSpecifiedThanUsedInTheQuery()
    {
        $subject = new RootLevelRestriction(['aTable']);
        $expression = $subject->buildExpression(['anotherTable' => ''], $this->expressionBuilder);
        $this->assertSame('', (string)$expression);
    }
}
