<?php
declare (strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Unit\Database\Query\Restriction;

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

use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;

class HiddenRestrictionTest extends AbstractRestrictionTestCase
{
    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
    }

    /**
     * @test
     */
    public function buildRestrictionsAddsHiddenWhereClause()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'enablecolumns' => [
                'disabled' => 'myHiddenField',
            ],
        ];
        $subject = new HiddenRestriction();
        $expression = $subject->buildExpression(['aTable' => ''], $this->expressionBuilder);
        $this->assertSame('"aTable"."myHiddenField" = 0', (string)$expression);
    }
}
