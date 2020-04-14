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

use TYPO3\CMS\Core\Database\Query\Restriction\FrontendGroupRestriction;

class FrontendGroupRestrictionTest extends AbstractRestrictionTestCase
{
    /**
     * @test
     */
    public function buildExpressionAddsNoAccessGroupWhereClause()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'enablecolumns' => [
                'fe_group' => 'myGroupField',
            ],
        ];
        $subject = new FrontendGroupRestriction([]);
        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        self::assertSame('("aTable"."myGroupField" IS NULL) OR ("aTable"."myGroupField" = \'\') OR ("aTable"."myGroupField" = \'0\')', (string)$expression);
    }

    /**
     * @test
     */
    public function buildExpressionAddsGroupWhereClause()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'enablecolumns' => [
                'fe_group' => 'myGroupField',
            ],
        ];
        $subject = new FrontendGroupRestriction([2, 3]);
        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        self::assertSame('("aTable"."myGroupField" IS NULL) OR ("aTable"."myGroupField" = \'\') OR ("aTable"."myGroupField" = \'0\') OR (FIND_IN_SET(\'2\', "aTable"."myGroupField")) OR (FIND_IN_SET(\'3\', "aTable"."myGroupField"))', (string)$expression);
    }
}
