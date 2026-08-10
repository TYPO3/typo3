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
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\CMS\Core\Domain\DateTimeFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

final class DefaultRestrictionContainerTest extends AbstractRestrictionTestCase
{
    #[Test]
    public function buildRestrictionsAddsAllDefaultRestrictions(): void
    {
        $this->get(TcaSchemaFactory::class)->rebuild(array_replace_recursive($GLOBALS['TCA'], [
            'aTable' => [
                'ctrl' => [
                    'delete' => 'deleted',
                    'enablecolumns' => [
                        'disabled' => 'myHiddenField',
                        'starttime' => 'myStartTimeField',
                        'endtime' => 'myEndTimeField',
                    ],
                ],
                'columns' => [
                    'myHiddenField' => [
                        'config' => [
                            'type' => 'check',
                        ],
                    ],
                    'myStartTimeField' => [
                        'config' => [
                            'type' => 'datetime',
                        ],
                    ],
                    'myEndTimeField' => [
                        'config' => [
                            'type' => 'datetime',
                        ],
                    ],
                ],
            ],
        ]));

        // 123 is floored to the full minute 120 when evaluating starttime / endtime
        $this->get(Context::class)->setAspect('date', new DateTimeAspect(DateTimeFactory::createFromTimestamp(123)));
        $subject = new DefaultRestrictionContainer();
        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        $expression = $this->expressionBuilder->and($expression);

        self::assertSame('(("aTable"."deleted" = 0) AND ("aTable"."myHiddenField" = 0) AND ("aTable"."myStartTimeField" <= 120) AND ((("aTable"."myEndTimeField" = 0) OR ("aTable"."myEndTimeField" > 120))))', (string)$expression);
    }
}
