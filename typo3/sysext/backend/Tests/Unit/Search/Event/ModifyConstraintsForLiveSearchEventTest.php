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

namespace TYPO3\CMS\Backend\Tests\Unit\Search\Event;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Search\Event\ModifyConstraintsForLiveSearchEvent;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\DemandProperty;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\DemandPropertyName;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\SearchDemand;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ModifyConstraintsForLiveSearchEventTest extends UnitTestCase
{
    #[Test]
    public function getTableNameReturnsTable(): void
    {
        $constraints = ['uid = 2', 'uid = 3', new CompositeExpression('foo')];
        $table = 'pages';
        $searchDemand = new SearchDemand([
            new DemandProperty(DemandPropertyName::query, 'foo'),
            new DemandProperty(DemandPropertyName::limit, 42),
        ]);
        $event = new ModifyConstraintsForLiveSearchEvent(
            $constraints,
            $table,
            $searchDemand,
        );

        self::assertSame($table, $event->getTableName());
    }

    #[Test]
    public function getSearchDemandReturnsSearchDemand(): void
    {
        $constraints = ['uid = 2', 'uid = 3', new CompositeExpression('foo')];
        $table = 'pages';
        $searchDemand = new SearchDemand([
            new DemandProperty(DemandPropertyName::query, 'foo'),
            new DemandProperty(DemandPropertyName::limit, 42),
        ]);
        $event = new ModifyConstraintsForLiveSearchEvent(
            $constraints,
            $table,
            $searchDemand,
        );

        self::assertSame($searchDemand, $event->getSearchDemand());
    }

    #[Test]
    public function getConstraintsReturnsConstraints(): void
    {
        $constraints = ['uid = 2', 'uid = 3', new CompositeExpression('foo')];
        $table = 'pages';
        $searchDemand = new SearchDemand([
            new DemandProperty(DemandPropertyName::query, 'foo'),
            new DemandProperty(DemandPropertyName::limit, 42),
        ]);
        $event = new ModifyConstraintsForLiveSearchEvent(
            $constraints,
            $table,
            $searchDemand,
        );

        self::assertSame($constraints, $event->getConstraints());
    }

    #[Test]
    public function addConstraintAsStringModifiesConstraints(): void
    {
        $constraints = ['uid = 2', 'uid = 3', new CompositeExpression('foo')];
        $table = 'pages';
        $searchDemand = new SearchDemand([
            new DemandProperty(DemandPropertyName::query, 'foo'),
            new DemandProperty(DemandPropertyName::limit, 42),
        ]);
        $event = new ModifyConstraintsForLiveSearchEvent(
            $constraints,
            $table,
            $searchDemand,
        );

        $constraint = 'uid = 5';
        $event->addConstraint($constraint);

        self::assertSame(array_merge($constraints, [$constraint]), $event->getConstraints());
    }

    #[Test]
    public function addConstraintAsCompositeModifiesConstraints(): void
    {
        $constraints = ['uid = 2', 'uid = 3', new CompositeExpression('foo')];
        $table = 'pages';
        $searchDemand = new SearchDemand([
            new DemandProperty(DemandPropertyName::query, 'foo'),
            new DemandProperty(DemandPropertyName::limit, 42),
        ]);
        $event = new ModifyConstraintsForLiveSearchEvent(
            $constraints,
            $table,
            $searchDemand,
        );

        $constraint = new CompositeExpression('bar');
        $event->addConstraint($constraint);

        self::assertSame(array_merge($constraints, [$constraint]), $event->getConstraints());
    }

    #[Test]
    public function addConstraintAsMultipleCallsModifiesConstraints(): void
    {
        $constraints = ['uid = 2', 'uid = 3', new CompositeExpression('foo')];
        $table = 'pages';
        $searchDemand = new SearchDemand([
            new DemandProperty(DemandPropertyName::query, 'foo'),
            new DemandProperty(DemandPropertyName::limit, 42),
        ]);
        $event = new ModifyConstraintsForLiveSearchEvent(
            $constraints,
            $table,
            $searchDemand,
        );

        $constraint1 = new CompositeExpression('bar');
        $event->addConstraint($constraint1);

        $constraint2 = 'uid = 5';
        $event->addConstraint($constraint2);

        self::assertSame(array_merge($constraints, [$constraint1, $constraint2]), $event->getConstraints());
    }

    #[Test]
    public function addConstraintsModifiesConstraints(): void
    {
        $constraints = ['uid = 2', 'uid = 3', new CompositeExpression('foo')];
        $table = 'pages';
        $searchDemand = new SearchDemand([
            new DemandProperty(DemandPropertyName::query, 'foo'),
            new DemandProperty(DemandPropertyName::limit, 42),
        ]);
        $event = new ModifyConstraintsForLiveSearchEvent(
            $constraints,
            $table,
            $searchDemand,
        );

        $addedConstraints = [
            'uid = 5',
            new CompositeExpression('bar'),
        ];
        $event->addConstraints(...$addedConstraints);

        self::assertSame(array_merge($constraints, $addedConstraints), $event->getConstraints());
    }

}
