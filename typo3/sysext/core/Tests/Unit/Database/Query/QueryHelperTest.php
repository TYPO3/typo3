<?php
declare (strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Unit\Database\Query;

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

use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Query helper test
 */
class QueryHelperTest extends UnitTestCase
{
    /**
     * Test cases for stripping of leading logical operators in where constraints.
     *
     * @return array
     */
    public function stripLogicalOperatorPrefixDataProvider(): array
    {
        return [
            'unprefixed input' => ['1=1', '1=1'],
            'leading/trailing whitespace is removed' => [' 1=1 ', '1=1'],
            'AND' => ['AND 1=1', '1=1'],
            'AND with leading space' => ['	AND 1=1', '1=1'],
            'AND with mixed whitespace' => [' 	 AND 1<>1', '1<>1'],
            'AND with opening bracket' => ['AND (1=1)', '(1=1)'],
            'AND without whitespace before bracket' => ['AND(1=1)', '(1=1)'],
            'AND within input' => ['1=1 AND 2=2', '1=1 AND 2=2'],
            'OR' => ['OR 1=1', '1=1'],
            'OR with leading space' => ['	OR 1=1', '1=1'],
            'OR with mixed whitespace' => [' 	 OR 1<>1', '1<>1'],
            'OR with opening bracket' => ['OR (1=1)', '(1=1)'],
            'OR without whitespace before bracket' => ['OR(1=1)', '(1=1)'],
            'OR within input' => ['1=1 OR 2=2', '1=1 OR 2=2'],
        ];
    }

    /**
     * @test
     * @dataProvider stripLogicalOperatorPrefixDataProvider
     * @param string $input
     * @param string $expectedSql
     */
    public function stripLogicalOperatorPrefixRemovesConstraintPrefixes(string $input, string $expectedSql)
    {
        $this->assertSame($expectedSql, QueryHelper::stripLogicalOperatorPrefix($input));
    }

    /**
     * Test cases for parsing ORDER BY SQL fragments
     *
     * @return array
     */
    public function parseOrderByDataProvider(): array
    {
        return [
            'single field' => [
                'aField',
                [
                    ['aField', null],
                ],
            ],
            'single field with leading whitespace' => [
                ' aField',
                [
                    ['aField', null],
                ],
            ],
            'prefixed single field' => [
                'ORDER BY aField',
                [
                    ['aField', null],
                ],
            ],
            'prefixed single field with leading whitespace' => [
                ' ORDER BY aField',
                [
                    ['aField', null],
                ],
            ],
            'single field with direction' => [
                'aField DESC',
                [
                    ['aField', 'DESC'],
                ],
            ],
            'multiple fields' => [
                'aField,anotherField, aThirdField',
                [
                    ['aField', null],
                    ['anotherField', null],
                    ['aThirdField', null]
                ],
            ],
            'multiple fields with direction' => [
                'aField ASC,anotherField, aThirdField DESC',
                [
                    ['aField', 'ASC'],
                    ['anotherField', null],
                    ['aThirdField', 'DESC']
                ],
            ],
            'prefixed multiple fields with direction' => [
                'ORDER BY aField ASC,anotherField, aThirdField DESC',
                [
                    ['aField', 'ASC'],
                    ['anotherField', null],
                    ['aThirdField', 'DESC']
                ],
            ],
            'with table prefix' => [
                'ORDER BY be_groups.title',
                [
                    ['be_groups.title', null]
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider parseOrderByDataProvider
     * @param string $input
     * @param array $expectedResult
     */
    public function parseOrderByTest(string $input, array $expectedResult)
    {
        $this->assertSame($expectedResult, QueryHelper::parseOrderBy($input));
    }

    /**
     * Test cases for parsing ORDER BY SQL fragments
     *
     * @return array
     */
    public function parseGroupByDataProvider(): array
    {
        return [
            'single field' => [
                'aField',
                ['aField'],
            ],
            'single field with leading whitespace' => [
                ' aField',
                ['aField'],
            ],
            'prefixed single field' => [
                'GROUP BY aField',
                ['aField'],
            ],
            'prefixed single field with leading whitespace' => [
                ' GROUP BY aField',
                ['aField'],
            ],
            'multiple fields' => [
                'aField,anotherField, aThirdField',
                ['aField', 'anotherField', 'aThirdField']
            ],
            'prefixed multiple fields' => [
                'GROUP BY aField,anotherField, aThirdField',
                ['aField', 'anotherField', 'aThirdField']
            ],
            'with table prefix' => [
                'GROUP BY be_groups.title',
                ['be_groups.title']
            ],
        ];
    }

    /**
     * @test
     * @dataProvider parseGroupByDataProvider
     * @param string $input
     * @param array $expectedResult
     */
    public function parseGroupByTest(string $input, array $expectedResult)
    {
        $this->assertSame($expectedResult, QueryHelper::parseGroupBy($input));
    }
}
