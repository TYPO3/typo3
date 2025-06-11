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
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendGroupRestriction;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

final class FrontendGroupRestrictionTest extends AbstractRestrictionTestCase
{
    #[Test]
    public function buildExpressionAddsNoAccessGroupWhereClause(): void
    {
        $this->get(TcaSchemaFactory::class)->rebuild(array_replace_recursive($GLOBALS['TCA'], [
            'aTable' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'fe_group' => 'myGroupField',
                    ],
                ],
                'columns' => [
                    'myGroupField' => [
                        'config' => [
                            'type' => 'select',
                        ],
                    ],
                ],
            ],
            'bTable' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'fe_group' => 'myOtherGroupField',
                    ],
                ],
                'columns' => [
                    'myOtherGroupField' => [
                        'config' => [
                            'type' => 'select',
                        ],
                    ],
                ],
            ],
        ]));

        $subject = new FrontendGroupRestriction([]);
        $expression = $subject->buildExpression(
            [
                'aTable' => 'aTable',
                'bTable' => 'bTable',
            ],
            $this->expressionBuilder,
        );
        self::assertSame(
            '(((("aTable"."myGroupField" IS NULL) OR ("aTable"."myGroupField" = \'\') OR ("aTable"."myGroupField" = \'0\'))) AND ((("bTable"."myOtherGroupField" IS NULL) OR ("bTable"."myOtherGroupField" = \'\') OR ("bTable"."myOtherGroupField" = \'0\'))))',
            (string)$expression,
        );
    }

    #[Test]
    public function buildExpressionAddsGroupWhereClause(): void
    {
        $this->get(TcaSchemaFactory::class)->rebuild(array_replace_recursive($GLOBALS['TCA'], [
            'aTable' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'fe_group' => 'myGroupField',
                    ],
                ],
                'columns' => [
                    'myGroupField' => [
                        'config' => [
                            'type' => 'select',
                        ],
                    ],
                ],
            ],
            'bTable' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'fe_group' => 'myOtherGroupField',
                    ],
                ],
                'columns' => [
                    'myOtherGroupField' => [
                        'config' => [
                            'type' => 'select',
                        ],
                    ],
                ],
            ],
        ]));

        $subject = new FrontendGroupRestriction([2, 3]);
        $expression = $subject->buildExpression(
            [
                'aTable' => 'aTable',
                'bTable' => 'bTable',
            ],
            $this->expressionBuilder,
        );
        self::assertSame(
            '(((("aTable"."myGroupField" IS NULL) OR ("aTable"."myGroupField" = \'\') OR ("aTable"."myGroupField" = \'0\') OR (FIND_IN_SET(\'2\', "aTable"."myGroupField")) OR (FIND_IN_SET(\'3\', "aTable"."myGroupField")))) AND ((("bTable"."myOtherGroupField" IS NULL) OR ("bTable"."myOtherGroupField" = \'\') OR ("bTable"."myOtherGroupField" = \'0\') OR (FIND_IN_SET(\'2\', "bTable"."myOtherGroupField")) OR (FIND_IN_SET(\'3\', "bTable"."myOtherGroupField")))))',
            (string)$expression,
        );
    }
}
