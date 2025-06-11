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
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

final class HiddenRestrictionTest extends AbstractRestrictionTestCase
{
    #[Test]
    public function buildRestrictionsAddsHiddenWhereClause(): void
    {
        $this->get(TcaSchemaFactory::class)->rebuild(array_replace_recursive($GLOBALS['TCA'], [
            'aTable' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'disabled' => 'myHiddenField',
                    ],
                ],
                'columns' => [
                    'myHiddenField' => [
                        'config' => [
                            'type' => 'check',
                        ],
                    ],
                ],
            ],
        ]));
        $subject = new HiddenRestriction();
        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        self::assertSame('"aTable"."myHiddenField" = 0', (string)$expression);
    }
}
