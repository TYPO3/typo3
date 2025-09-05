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
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\LimitToTablesRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

final class LimitToTablesRestrictionContainerTest extends AbstractRestrictionTestCase
{
    #[Test]
    public function buildExpressionAddsRestrictionsOnlyToGivenAlias(): void
    {
        $this->get(TcaSchemaFactory::class)->rebuild(array_replace_recursive($GLOBALS['TCA'], [
            'bTable' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'disabled' => 'hidden',
                    ],
                ],
                'columns' => [
                    'hidden' => [
                        'config' => [
                            'type' => 'check',
                        ],
                    ],
                ],
            ],
        ]));

        $subject = new LimitToTablesRestrictionContainer();
        $subject->addForTables(new HiddenRestriction(), ['bt']);
        $expression = $subject->buildExpression(['aTable' => 'aTable', 'bTable' => 'bTable', 'bt' => 'bTable'], $this->expressionBuilder);

        self::assertSame('"bt"."hidden" = 0', (string)$expression);
    }

    #[Test]
    public function buildExpressionAddsRestrictionsOfDefaultRestrictionContainerOnlyToGivenAlias(): void
    {
        $this->get(TcaSchemaFactory::class)->rebuild(array_replace_recursive($GLOBALS['TCA'], [
            'bTable' => [
                'ctrl' => [
                    'delete' => 'deleted',
                    'enablecolumns' => [
                        'disabled' => 'hidden',
                    ],
                ],
                'columns' => [
                    'hidden' => [
                        'config' => [
                            'type' => 'check',
                        ],
                    ],
                ],
            ],
        ]));

        $subject = new LimitToTablesRestrictionContainer();
        $subject->addForTables(new DefaultRestrictionContainer(), ['bt']);
        $expression = $subject->buildExpression(['aTable' => 'aTable', 'bTable' => 'bTable', 'bt' => 'bTable'], $this->expressionBuilder);

        self::assertSame('(("bt"."deleted" = 0) AND ("bt"."hidden" = 0))', (string)$expression);
    }

    #[Test]
    public function removeByTypeRemovesRestrictionsByTypeAlsoFromDefaultRestrictionContainer(): void
    {
        $this->get(TcaSchemaFactory::class)->rebuild(array_replace_recursive($GLOBALS['TCA'], [
            'bTable' => [
                'ctrl' => [
                    'delete' => 'deleted',
                    'enablecolumns' => [
                        'disabled' => 'hidden',
                    ],
                ],
                'columns' => [
                    'hidden' => [
                        'config' => [
                            'type' => 'datetime',
                        ],
                    ],
                ],
            ],
        ]));

        $subject = new LimitToTablesRestrictionContainer();
        $subject->addForTables(new DefaultRestrictionContainer(), ['bt']);
        $subject->removeByType(DeletedRestriction::class);
        $expression = $subject->buildExpression(['aTable' => 'aTable', 'bTable' => 'bTable', 'bt' => 'bTable'], $this->expressionBuilder);

        self::assertSame('"bt"."hidden" = 0', (string)$expression);
    }

    #[Test]
    public function removeByTypeRemovesRestrictionsByTypeAlsoFromAnyRestrictionContainer(): void
    {
        $subject = new LimitToTablesRestrictionContainer();
        $containerMock = $this->createMock(QueryRestrictionContainerInterface::class);
        $containerMock->expects($this->atLeastOnce())->method('removeByType')->with(DeletedRestriction::class);
        $containerMock->expects($this->atLeastOnce())->method('buildExpression')->with(['bt' => 'bTable'], $this->expressionBuilder)
            ->willReturn($this->expressionBuilder->and(...[]));
        $subject->addForTables($containerMock, ['bt']);
        $subject->removeByType(DeletedRestriction::class);
        $subject->buildExpression(['aTable' => 'aTable', 'bTable' => 'bTable', 'bt' => 'bTable'], $this->expressionBuilder);
    }
}
