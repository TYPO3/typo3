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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\InstantiatableAbstractRestrictionContainer;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockEnforceableQueryRestriction;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockQueryRestriction;

final class AbstractRestrictionContainerTest extends AbstractRestrictionTestCase
{
    #[Test]
    public function enforceableRestrictionsAreKeptWhenRemoveAllIsCalled(): void
    {
        $restriction = $this->createMock(MockEnforceableQueryRestriction::class);
        $restriction->expects($this->atLeastOnce())->method('buildExpression')->with(['aTable' => 'aTable'], $this->expressionBuilder)
            ->willReturn(CompositeExpression::and('"aTable"."pid" = 0'));
        $restriction->method('isEnforced')->willReturn(true);

        $subject = new InstantiatableAbstractRestrictionContainer();
        $subject->add($restriction);
        $subject->removeAll();

        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        self::assertSame('"aTable"."pid" = 0', (string)$expression);
    }

    #[Test]
    public function enforceableRestrictionsWillBeRemovedWhenRemovedByType(): void
    {
        $restriction = $this->createMock(MockEnforceableQueryRestriction::class);
        $restriction->expects($this->never())->method('buildExpression');
        $restriction->method('isEnforced')->willReturn(true);

        $subject = new InstantiatableAbstractRestrictionContainer();
        $subject->add($restriction);
        $subject->removeByType(get_class($restriction));

        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        self::assertSame('', (string)$expression);
    }

    #[Test]
    public function crossClassWillBeRemovedWhenRemovedByType(): void
    {
        $restriction = new class () extends HiddenRestriction {};

        $subject = new InstantiatableAbstractRestrictionContainer();
        $subject->add($restriction);
        $subject->removeByType(HiddenRestriction::class);

        $GLOBALS['TCA']['aTable']['ctrl']['enablecolumns']['disabled'] = 'hidden';
        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        self::assertSame('', (string)$expression);
    }

    #[Test]
    public function enforceableRestrictionsWillBeRemovedWhenRemovedByTypeAndRemovedAllIsAdditionallyCalled(): void
    {
        $restriction = $this->createMock(MockEnforceableQueryRestriction::class);
        $restriction->expects($this->never())->method('buildExpression');
        $restriction->method('isEnforced')->willReturn(true);

        $subject = new InstantiatableAbstractRestrictionContainer();
        $subject->add($restriction);
        $subject->removeByType(get_class($restriction));
        $subject->removeAll();

        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        self::assertSame('', (string)$expression);
    }

    #[Test]
    public function notEnforceableRestrictionsAreRemovedWhenRemoveAllIsCalled(): void
    {
        $restriction = $this->createMock(MockQueryRestriction::class);
        $restriction->expects($this->never())->method('buildExpression');

        $subject = new InstantiatableAbstractRestrictionContainer();
        $subject->add($restriction);
        $subject->removeAll();

        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        self::assertSame('', (string)$expression);
    }

    #[Test]
    public function enforceableRestrictionsThatDeclareThemselvesNonStickyAreRemovedWhenRemoveAllIsCalled(): void
    {
        $restriction = $this->createMock(MockEnforceableQueryRestriction::class);
        $restriction->expects($this->never())->method('buildExpression');
        $restriction->method('isEnforced')->willReturn(false);

        $subject = new InstantiatableAbstractRestrictionContainer();
        $subject->add($restriction);
        $subject->removeAll();

        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        self::assertSame('', (string)$expression);
    }
}
