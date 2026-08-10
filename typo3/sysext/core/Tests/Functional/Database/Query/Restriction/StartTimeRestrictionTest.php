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
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Domain\DateTimeFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

final class StartTimeRestrictionTest extends AbstractRestrictionTestCase
{
    #[Test]
    public function buildRestrictionsFallsBackToDateAspectWithMinutePrecision(): void
    {
        $this->get(TcaSchemaFactory::class)->rebuild(array_replace_recursive($GLOBALS['TCA'], [
            'aTable' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'starttime' => 'myStartTimeField',
                    ],
                ],
                'columns' => [
                    'myStartTimeField' => [
                        'config' => [
                            'type' => 'datetime',
                        ],
                    ],
                ],
            ],
        ]));

        $this->get(Context::class)->setAspect('date', new DateTimeAspect(DateTimeFactory::createFromTimestamp(1698750123)));

        $subject = new StartTimeRestriction();
        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        self::assertSame('"aTable"."myStartTimeField" <= 1698750120', (string)$expression);
    }

    #[Test]
    public function buildRestrictionsAddsStartTimeWhereClause(): void
    {
        $this->get(TcaSchemaFactory::class)->rebuild(array_replace_recursive($GLOBALS['TCA'], [
            'aTable' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'starttime' => 'myStartTimeField',
                    ],
                ],
                'columns' => [
                    'myStartTimeField' => [
                        'config' => [
                            'type' => 'datetime',
                        ],
                    ],
                ],
            ],
        ]));

        $subject = new StartTimeRestriction(42);
        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        self::assertSame('"aTable"."myStartTimeField" <= 42', (string)$expression);
    }
}
