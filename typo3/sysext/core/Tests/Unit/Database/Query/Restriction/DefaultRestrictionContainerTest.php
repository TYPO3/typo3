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

use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;

class DefaultRestrictionContainerTest extends AbstractRestrictionTestCase
{
    /**
     * @test
     */
    public function buildRestrictionsAddsAllDefaultRestrictions()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'delete' => 'deleted',
            'enablecolumns' => [
                'disabled' => 'myHiddenField',
                'starttime' => 'myStartTimeField',
                'endtime' => 'myEndTimeField',
            ],
        ];
        $GLOBALS['SIM_ACCESS_TIME'] = 123;
        $subject = new DefaultRestrictionContainer();
        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        $more = [];
        $more[] = $expression;
        $expression = $this->expressionBuilder->andX($expression);

        self::assertSame('("aTable"."deleted" = 0) AND ("aTable"."myHiddenField" = 0) AND ("aTable"."myStartTimeField" <= 123) AND (("aTable"."myEndTimeField" = 0) OR ("aTable"."myEndTimeField" > 123))', (string)$expression);
    }
}
