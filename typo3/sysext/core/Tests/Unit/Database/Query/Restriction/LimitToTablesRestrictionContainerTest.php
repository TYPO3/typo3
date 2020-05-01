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

namespace TYPO3\CMS\Core\Tests\Unit\Database\Query\Restriction;

use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\LimitToTablesRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;

class LimitToTablesRestrictionContainerTest extends AbstractRestrictionTestCase
{
    /**
     * @test
     */
    public function buildExpressionAddsRestrictionsOnlyToGivenAlias(): void
    {
        $GLOBALS['TCA']['bTable']['ctrl']['enablecolumns']['disabled'] = 'hidden';
        $subject = new LimitToTablesRestrictionContainer();
        $subject->addForTables(new HiddenRestriction(), ['bt']);
        $expression = $subject->buildExpression(['aTable' => 'aTable', 'bTable' => 'bTable', 'bt' => 'bTable'], $this->expressionBuilder);

        self::assertSame('"bt"."hidden" = 0', (string)$expression);
    }

    /**
     * @test
     */
    public function buildExpressionAddsRestrictionsOfDefaultRestrictionContainerOnlyToGivenAlias(): void
    {
        $GLOBALS['TCA']['bTable']['ctrl']['enablecolumns']['disabled'] = 'hidden';
        $GLOBALS['TCA']['bTable']['ctrl']['delete'] = 'deleted';
        $subject = new LimitToTablesRestrictionContainer();
        $subject->addForTables(new DefaultRestrictionContainer(), ['bt']);
        $expression = $subject->buildExpression(['aTable' => 'aTable', 'bTable' => 'bTable', 'bt' => 'bTable'], $this->expressionBuilder);

        self::assertSame('("bt"."deleted" = 0) AND ("bt"."hidden" = 0)', (string)$expression);
    }

    /**
     * @test
     */
    public function removeByTypeRemovesRestrictionsByTypeAlsoFromDefaultRestrictionContainer(): void
    {
        $GLOBALS['TCA']['bTable']['ctrl']['enablecolumns']['disabled'] = 'hidden';
        $GLOBALS['TCA']['bTable']['ctrl']['delete'] = 'deleted';
        $subject = new LimitToTablesRestrictionContainer();
        $subject->addForTables(new DefaultRestrictionContainer(), ['bt']);
        $subject->removeByType(DeletedRestriction::class);
        $expression = $subject->buildExpression(['aTable' => 'aTable', 'bTable' => 'bTable', 'bt' => 'bTable'], $this->expressionBuilder);

        self::assertSame('"bt"."hidden" = 0', (string)$expression);
    }

    /**
     * @test
     */
    public function removeByTypeRemovesRestrictionsByTypeAlsoFromAnyRestrictionContainer(): void
    {
        $GLOBALS['TCA']['bTable']['ctrl']['enablecolumns']['disabled'] = 'hidden';
        $GLOBALS['TCA']['bTable']['ctrl']['delete'] = 'deleted';
        $subject = new LimitToTablesRestrictionContainer();
        $containerProphecy = $this->prophesize(QueryRestrictionContainerInterface::class);
        $containerProphecy->removeByType(DeletedRestriction::class)->shouldBeCalled();
        $containerProphecy->buildExpression(['bt' => 'bTable'], $this->expressionBuilder)->willReturn($this->expressionBuilder->andX([]))->shouldBeCalled();
        $subject->addForTables($containerProphecy->reveal(), ['bt']);
        $subject->removeByType(DeletedRestriction::class);
        $subject->buildExpression(['aTable' => 'aTable', 'bTable' => 'bTable', 'bt' => 'bTable'], $this->expressionBuilder);
    }

    /**
     * @test
     */
    public function buildRestrictionsThrowsExceptionWhenGivenAliasIsNotInQueriedTables(): void
    {
        $this->expectException(\LogicException::class);
        $subject = new LimitToTablesRestrictionContainer();
        $subject->addForTables(new HiddenRestriction(), ['bt']);
        $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
    }
}
