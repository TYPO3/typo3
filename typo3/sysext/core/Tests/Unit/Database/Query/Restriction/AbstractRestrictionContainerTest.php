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

use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Restriction\EnforceableQueryRestrictionInterface;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\InstantiatableAbstractRestrictionContainer;

class AbstractRestrictionContainerTest extends AbstractRestrictionTestCase
{
    /**
     * @test
     */
    public function enforceableRestrictionsAreKeptWhenRemoveAllIsCalled()
    {
        $restriction = $this->prophesize();
        $restriction->willImplement(QueryRestrictionInterface::class);
        $restriction->willImplement(EnforceableQueryRestrictionInterface::class);
        $restriction->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder)
            ->shouldBeCalled()
            ->willReturn(new CompositeExpression(CompositeExpression::TYPE_AND, ['"aTable"."pid" = 0']));
        $restriction->isEnforced()->willReturn(true);

        $subject = new InstantiatableAbstractRestrictionContainer();
        $subject->add($restriction->reveal());
        $subject->removeAll();

        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        self::assertSame('"aTable"."pid" = 0', (string)$expression);
    }

    /**
     * @test
     */
    public function enforceableRestrictionsWillBeRemovedWhenRemovedByType()
    {
        $restriction = $this->prophesize();
        $restriction->willImplement(QueryRestrictionInterface::class);
        $restriction->willImplement(EnforceableQueryRestrictionInterface::class);
        $restriction->buildExpression()->shouldNotBeCalled();
        $restriction->isEnforced()->willReturn(true);

        $subject = new InstantiatableAbstractRestrictionContainer();
        $restriction = $restriction->reveal();
        $subject->add($restriction);
        $subject->removeByType(get_class($restriction));

        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        self::assertSame('', (string)$expression);
    }

    /**
     * @test
     */
    public function enforceableRestrictionsWillBeRemovedWhenRemovedByTypeAndRemovedAllIsAdditionallyCalled()
    {
        $restriction = $this->prophesize();
        $restriction->willImplement(QueryRestrictionInterface::class);
        $restriction->willImplement(EnforceableQueryRestrictionInterface::class);
        $restriction->buildExpression()->shouldNotBeCalled();
        $restriction->isEnforced()->willReturn(true);

        $subject = new InstantiatableAbstractRestrictionContainer();
        $restriction = $restriction->reveal();
        $subject->add($restriction);
        $subject->removeByType(get_class($restriction));
        $subject->removeAll();

        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        self::assertSame('', (string)$expression);
    }

    /**
     * @test
     */
    public function notEnforceableRestrictionsAreRemovedWhenRemoveAllIsCalled()
    {
        $restriction = $this->prophesize();
        $restriction->willImplement(QueryRestrictionInterface::class);
        $restriction->buildExpression()->shouldNotBeCalled();

        $subject = new InstantiatableAbstractRestrictionContainer();
        $subject->add($restriction->reveal());
        $subject->removeAll();

        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        self::assertSame('', (string)$expression);
    }

    /**
     * @test
     */
    public function enforceableRestrictionsThatDeclareThemselvesNonStickyAreRemovedWhenRemoveAllIsCalled()
    {
        $restriction = $this->prophesize();
        $restriction->willImplement(QueryRestrictionInterface::class);
        $restriction->willImplement(EnforceableQueryRestrictionInterface::class);
        $restriction->buildExpression()->shouldNotBeCalled();
        $restriction->isEnforced()->willReturn(false);

        $subject = new InstantiatableAbstractRestrictionContainer();
        $subject->add($restriction->reveal());
        $subject->removeAll();

        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        self::assertSame('', (string)$expression);
    }
}
