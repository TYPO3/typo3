<?php
namespace TYPO3\CMS\Install\Tests\Unit\Updates\RowUpdater;

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

use Prophecy\Argument;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;
use TYPO3\CMS\Core\Tests\Unit\Resource\BaseTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\RowUpdater\L10nModeUpdater;

/**
 * Test Class for ContentTypesToTextMediaUpdate
 */
class L10nModeUpdaterTest extends BaseTestCase
{
    /**
     * @test
     */
    public function hasPotentialUpdateForTableThrowsExceptionIfGlobalsTcaIsNoArray()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1484176136);
        (new L10nModeUpdater())->hasPotentialUpdateForTable('someTable');
    }

    /**
     * @test
     */
    public function hasPotentialUpdateForTableReturnFalseForTableWithoutL10nMode()
    {
        $GLOBALS['TCA'] = [
            'testTable' => [
                'ctrl' => [
                    'languageField' => 'sys_language_uid',
                    'transOrigPointerField' => 'l10n_parent',
                ],
                'columns' => [
                    'testField' => [
                        'label' => 'someLabel',
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
        ];
        $subject = new L10nModeUpdater();
        $this->assertFalse($subject->hasPotentialUpdateForTable('testTable'));
    }

    /**
     * @test
     */
    public function hasPotentialUpdateForTableReturnTrueForTableWithL10nModeExclude()
    {
        $GLOBALS['TCA'] = [
            'testTable' => [
                'ctrl' => [
                    'languageField' => 'sys_language_uid',
                    'transOrigPointerField' => 'l10n_parent',
                ],
                'columns' => [
                    'testField' => [
                        'label' => 'someLabel',
                        'l10n_mode' => 'exclude',
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
        ];

        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $connectionPoolProphecy->getQueryBuilderForTable('testTable')->willReturn($queryBuilderProphecy->reveal());
        $restrictionBuilderProphecy = $this->prophesize(QueryRestrictionContainerInterface::class);
        $queryBuilderProphecy->getRestrictions()->willReturn($restrictionBuilderProphecy->reveal());
        $queryBuilderProphecy->from('testTable');
        $expressionBuilderProphecy = $this->prophesize(ExpressionBuilder::class);
        $queryBuilderProphecy->expr()->willReturn($expressionBuilderProphecy->reveal());
        $queryBuilderProphecy->createNamedParameter(Argument::cetera())->willReturnArgument(0);
        $expressionBuilderProphecy->gt('sys_language_uid', 0);
        $expressionBuilderProphecy->gt('l10n_parent', 0);
        $queryBuilderProphecy->select('uid', 'l10n_parent')->willReturn($queryBuilderProphecy->reveal());
        $queryBuilderProphecy->andWhere(Argument::cetera())->willReturn($queryBuilderProphecy->reveal());
        $queryBuilderProphecy->execute()->willReturn([
            [
                'uid' => 23,
                'l10n_parent' => 42,
            ]
        ]);

        $subject = new L10nModeUpdater();
        $this->assertTrue($subject->hasPotentialUpdateForTable('testTable'));
    }

    /**
     * @test
     */
    public function hasPotentialUpdateForTableReturnTrueForTableWithBehaviourAllowLanguageSynchronization()
    {
        $GLOBALS['TCA'] = [
            'testTable' => [
                'ctrl' => [
                    'languageField' => 'sys_language_uid',
                    'transOrigPointerField' => 'l10n_parent',
                ],
                'columns' => [
                    'testField' => [
                        'label' => 'someLabel',
                        'config' => [
                            'type' => 'input',
                            'behaviour' => [
                                'allowLanguageSynchronization' => true,
                            ]
                        ],
                    ],
                ],
            ],
        ];

        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $connectionPoolProphecy->getQueryBuilderForTable('testTable')->willReturn($queryBuilderProphecy->reveal());
        $restrictionBuilderProphecy = $this->prophesize(QueryRestrictionContainerInterface::class);
        $queryBuilderProphecy->getRestrictions()->willReturn($restrictionBuilderProphecy->reveal());
        $queryBuilderProphecy->from('testTable');
        $expressionBuilderProphecy = $this->prophesize(ExpressionBuilder::class);
        $queryBuilderProphecy->expr()->willReturn($expressionBuilderProphecy->reveal());
        $expressionBuilderProphecy = $this->prophesize(ExpressionBuilder::class);
        $queryBuilderProphecy->expr()->willReturn($expressionBuilderProphecy->reveal());
        $queryBuilderProphecy->createNamedParameter(Argument::cetera())->willReturnArgument(0);
        $expressionBuilderProphecy->gt('sys_language_uid', 0);
        $expressionBuilderProphecy->gt('l10n_parent', 0);
        $queryBuilderProphecy->select('uid', 'l10n_parent')->willReturn($queryBuilderProphecy->reveal());
        $queryBuilderProphecy->andWhere(Argument::cetera())->willReturn($queryBuilderProphecy->reveal());
        $queryBuilderProphecy->execute()->willReturn([
            [
                'uid' => 23,
                'l10n_parent' => 42,
            ]
        ]);

        $subject = new L10nModeUpdater();
        $this->assertTrue($subject->hasPotentialUpdateForTable('testTable'));
    }
}
