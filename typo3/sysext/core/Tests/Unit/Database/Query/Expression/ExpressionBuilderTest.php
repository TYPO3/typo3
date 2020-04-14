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

namespace TYPO3\CMS\Core\Tests\Unit\Database\Query\Expression;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ExpressionBuilderTest extends UnitTestCase
{
    /**
     * @var Connection
     */
    protected $connectionProphet;

    /**
     * @var ExpressionBuilder
     */
    protected $subject;

    /**
     * @var string
     */
    protected $testTable = 'testTable';

    /**
     * Create a new database connection mock object for every test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @var Connection|ObjectProphecy $connectionProphet */
        $this->connectionProphet = $this->prophesize(Connection::class);
        $this->connectionProphet->quoteIdentifier(Argument::cetera())->willReturnArgument(0);

        $this->subject = new ExpressionBuilder($this->connectionProphet->reveal());
    }

    /**
     * @test
     */
    public function andXReturnType()
    {
        $result = $this->subject->andX('"uid" = 1', '"pid" = 0');

        self::assertInstanceOf(CompositeExpression::class, $result);
        self::assertSame(CompositeExpression::TYPE_AND, $result->getType());
    }

    /**
     * @test
     */
    public function orXReturnType()
    {
        $result = $this->subject->orX('"uid" = 1', '"uid" = 7');

        self::assertInstanceOf(CompositeExpression::class, $result);
        self::assertSame(CompositeExpression::TYPE_OR, $result->getType());
    }

    /**
     * @test
     */
    public function eqQuotesIdentifier()
    {
        $result = $this->subject->eq('aField', 1);

        $this->connectionProphet->quoteIdentifier('aField')->shouldHaveBeenCalled();
        self::assertSame('aField = 1', $result);
    }

    /**
     * @test
     */
    public function neqQuotesIdentifier()
    {
        $result = $this->subject->neq('aField', 1);

        $this->connectionProphet->quoteIdentifier('aField')->shouldHaveBeenCalled();
        self::assertSame('aField <> 1', $result);
    }

    /**
     * @test
     */
    public function ltQuotesIdentifier()
    {
        $result = $this->subject->lt('aField', 1);

        $this->connectionProphet->quoteIdentifier('aField')->shouldHaveBeenCalled();
        self::assertSame('aField < 1', $result);
    }

    /**
     * @test
     */
    public function lteQuotesIdentifier()
    {
        $result = $this->subject->lte('aField', 1);

        $this->connectionProphet->quoteIdentifier('aField')->shouldHaveBeenCalled();
        self::assertSame('aField <= 1', $result);
    }

    /**
     * @test
     */
    public function gtQuotesIdentifier()
    {
        $result = $this->subject->gt('aField', 1);

        $this->connectionProphet->quoteIdentifier('aField')->shouldHaveBeenCalled();
        self::assertSame('aField > 1', $result);
    }

    /**
     * @test
     */
    public function gteQuotesIdentifier()
    {
        $result = $this->subject->gte('aField', 1);

        $this->connectionProphet->quoteIdentifier('aField')->shouldHaveBeenCalled();
        self::assertSame('aField >= 1', $result);
    }

    /**
     * @test
     */
    public function isNullQuotesIdentifier()
    {
        $result = $this->subject->isNull('aField');

        $this->connectionProphet->quoteIdentifier('aField')->shouldHaveBeenCalled();
        self::assertSame('aField IS NULL', $result);
    }

    /**
     * @test
     */
    public function isNotNullQuotesIdentifier()
    {
        $result = $this->subject->isNotNull('aField');

        $this->connectionProphet->quoteIdentifier('aField')->shouldHaveBeenCalled();
        self::assertSame('aField IS NOT NULL', $result);
    }

    /**
     * @test
     */
    public function likeQuotesIdentifier()
    {
        $result = $this->subject->like('aField', "'aValue%'");

        $this->connectionProphet->quoteIdentifier('aField')->shouldHaveBeenCalled();
        self::assertSame("aField LIKE 'aValue%'", $result);
    }

    /**
     * @test
     */
    public function notLikeQuotesIdentifier()
    {
        $result = $this->subject->notLike('aField', "'aValue%'");

        $this->connectionProphet->quoteIdentifier('aField')->shouldHaveBeenCalled();
        self::assertSame("aField NOT LIKE 'aValue%'", $result);
    }

    /**
     * @test
     */
    public function inWithStringQuotesIdentifier()
    {
        $result = $this->subject->in('aField', '1,2,3');

        $this->connectionProphet->quoteIdentifier('aField')->shouldHaveBeenCalled();
        self::assertSame('aField IN (1,2,3)', $result);
    }

    /**
     * @test
     */
    public function inWithArrayQuotesIdentifier()
    {
        $result = $this->subject->in('aField', [1, 2, 3]);

        $this->connectionProphet->quoteIdentifier('aField')->shouldHaveBeenCalled();
        self::assertSame('aField IN (1, 2, 3)', $result);
    }

    /**
     * @test
     */
    public function notInWithStringQuotesIdentifier()
    {
        $result = $this->subject->notIn('aField', '1,2,3');

        $this->connectionProphet->quoteIdentifier('aField')->shouldHaveBeenCalled();
        self::assertSame('aField NOT IN (1,2,3)', $result);
    }

    /**
     * @test
     */
    public function notInWithArrayQuotesIdentifier()
    {
        $result = $this->subject->notIn('aField', [1, 2, 3]);

        $this->connectionProphet->quoteIdentifier('aField')->shouldHaveBeenCalled();
        self::assertSame('aField NOT IN (1, 2, 3)', $result);
    }

    /**
     * @test
     */
    public function inSetThrowsExceptionWithEmptyValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1459696089);
        $this->subject->inSet('aField', '');
    }

    /**
     * @test
     */
    public function inSetThrowsExceptionWithInvalidValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1459696090);
        $this->subject->inSet('aField', 'an,Invalid,Value');
    }

    /**
     * @test
     */
    public function inSetForMySQL()
    {
        $databasePlatform = $this->prophesize(MockPlatform::class);
        $databasePlatform->getName()->willReturn('mysql');

        $this->connectionProphet->quoteIdentifier(Argument::cetera())->will(function ($args) {
            return '`' . $args[0] . '`';
        });

        $this->connectionProphet->getDatabasePlatform()->willReturn($databasePlatform->reveal());

        $result = $this->subject->inSet('aField', "'1'");

        self::assertSame('FIND_IN_SET(\'1\', `aField`)', $result);
    }

    /**
     * @test
     */
    public function inSetForPostgreSQL()
    {
        $databasePlatform = $this->prophesize(MockPlatform::class);
        $databasePlatform->getName()->willReturn('postgresql');
        $databasePlatform->getStringLiteralQuoteCharacter()->willReturn('"');

        $this->connectionProphet->quote(',', Argument::cetera())->shouldBeCalled()->willReturn("','");
        $this->connectionProphet->quote("'1'", null)->shouldBeCalled()->willReturn("'1'");
        $this->connectionProphet->quoteIdentifier(Argument::cetera())->will(function ($args) {
            return '"' . $args[0] . '"';
        });

        $this->connectionProphet->getDatabasePlatform()->willReturn($databasePlatform->reveal());

        $result = $this->subject->inSet('aField', "'1'");

        self::assertSame('\'1\' = ANY(string_to_array("aField"::text, \',\'))', $result);
    }

    /**
     * @test
     */
    public function inSetForPostgreSQLWithColumn()
    {
        $databasePlatform = $this->prophesize(MockPlatform::class);
        $databasePlatform->getName()->willReturn('postgresql');

        $this->connectionProphet->quote(',', Argument::cetera())->shouldBeCalled()->willReturn("','");
        $this->connectionProphet->quoteIdentifier(Argument::cetera())->will(function ($args) {
            return '"' . $args[0] . '"';
        });

        $this->connectionProphet->getDatabasePlatform()->willReturn($databasePlatform->reveal());

        $result = $this->subject->inSet('aField', '"testtable"."uid"', true);

        self::assertSame('"testtable"."uid"::text = ANY(string_to_array("aField"::text, \',\'))', $result);
    }

    /**
     * @test
     */
    public function inSetForSQLite()
    {
        $databasePlatform = $this->prophesize(MockPlatform::class);
        $databasePlatform->getName()->willReturn('sqlite');
        $databasePlatform->getStringLiteralQuoteCharacter()->willReturn("'");

        $this->connectionProphet->quote(',', Argument::cetera())->shouldBeCalled()->willReturn("','");
        $this->connectionProphet->quote(',1,', Argument::cetera())->shouldBeCalled()->willReturn("'%,1,%'");
        $this->connectionProphet->quoteIdentifier(Argument::cetera())->will(function ($args) {
            return '"' . $args[0] . '"';
        });

        $this->connectionProphet->getDatabasePlatform()->willReturn($databasePlatform->reveal());

        $result = $this->subject->inSet('aField', "'1'");

        self::assertSame('instr(\',\'||"aField"||\',\', \'%,1,%\')', $result);
    }

    /**
     * @test
     */
    public function inSetForSQLiteWithQuoteCharactersInValue()
    {
        $databasePlatform = $this->prophesize(MockPlatform::class);
        $databasePlatform->getName()->willReturn('sqlite');
        $databasePlatform->getStringLiteralQuoteCharacter()->willReturn("'");

        $this->connectionProphet->quote(',', Argument::cetera())->shouldBeCalled()->willReturn("','");
        $this->connectionProphet->quote(',\'Some\'Value,', Argument::cetera())->shouldBeCalled()
            ->willReturn("',''Some''Value,'");
        $this->connectionProphet->quoteIdentifier(Argument::cetera())->will(function ($args) {
            return '"' . $args[0] . '"';
        });

        $this->connectionProphet->getDatabasePlatform()->willReturn($databasePlatform->reveal());

        $result = $this->subject->inSet('aField', "'''Some''Value'");

        self::assertSame('instr(\',\'||"aField"||\',\', \',\'\'Some\'\'Value,\')', $result);
    }

    /**
     * @test
     */
    public function inSetForSQLiteThrowsExceptionOnPositionalPlaceholder()
    {
        $databasePlatform = $this->prophesize(MockPlatform::class);
        $databasePlatform->getName()->willReturn('sqlite');
        $databasePlatform->getStringLiteralQuoteCharacter()->willReturn("'");

        $this->connectionProphet->getDatabasePlatform()->willReturn($databasePlatform->reveal());

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionCode(1476029421);

        $this->subject->inSet('aField', '?');
    }

    /**
     * @test
     */
    public function inSetForSQLiteThrowsExceptionOnNamedPlaceholder()
    {
        $databasePlatform = $this->prophesize(MockPlatform::class);
        $databasePlatform->getName()->willReturn('sqlite');
        $databasePlatform->getStringLiteralQuoteCharacter()->willReturn("'");

        $this->connectionProphet->getDatabasePlatform()->willReturn($databasePlatform->reveal());

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionCode(1476029421);

        $this->subject->inSet('aField', ':dcValue1');
    }

    /**
     * @test
     */
    public function inSetForMssql()
    {
        $databasePlatform = $this->prophesize(MockPlatform::class);
        $databasePlatform->getName()->willReturn('mssql');
        $databasePlatform->getStringLiteralQuoteCharacter()->willReturn('\'');

        $this->connectionProphet->quote('1', null)->shouldBeCalled()->willReturn("'1'");
        $this->connectionProphet->quote('1,%', null)->shouldBeCalled()->willReturn("'1,%'");
        $this->connectionProphet->quote('%,1', null)->shouldBeCalled()->willReturn("'%,1'");
        $this->connectionProphet->quote('%,1,%', null)->shouldBeCalled()->willReturn("'%,1,%'");
        $this->connectionProphet->quoteIdentifier(Argument::cetera())->will(function ($args) {
            return '[' . $args[0] . ']';
        });

        $this->connectionProphet->getDatabasePlatform()->willReturn($databasePlatform->reveal());

        $result = $this->subject->inSet('aField', "'1'");

        self::assertSame("([aField] = '1') OR ([aField] LIKE '1,%') OR ([aField] LIKE '%,1') OR ([aField] LIKE '%,1,%')", $result);
    }

    /**
     * @test
     */
    public function defaultBitwiseAnd()
    {
        $databasePlatform = $this->prophesize(MockPlatform::class);

        $this->connectionProphet->quoteIdentifier(Argument::cetera())->will(function ($args) {
            return '"' . $args[0] . '"';
        });

        $this->connectionProphet->getDatabasePlatform()->willReturn($databasePlatform->reveal());

        self::assertSame('"aField" & 1', $this->subject->bitAnd('aField', 1));
    }

    /**
     * @test
     */
    public function bitwiseAndForOracle()
    {
        $databasePlatform = $this->prophesize(MockPlatform::class);
        $databasePlatform->getName()->willReturn('pdo_oracle');

        $this->connectionProphet->quoteIdentifier(Argument::cetera())->will(function ($args) {
            return '"' . $args[0] . '"';
        });

        $this->connectionProphet->getDatabasePlatform()->willReturn($databasePlatform->reveal());

        self::assertSame('BITAND("aField", 1)', $this->subject->bitAnd('aField', 1));
    }

    /**
     * @test
     */
    public function maxQuotesIdentifier()
    {
        $this->connectionProphet->quoteIdentifier(Argument::cetera())->will(function ($args) {
            $platform = new MockPlatform();
            return $platform->quoteIdentifier($args[0]);
        });

        self::assertSame('MAX("tableName"."fieldName")', $this->subject->max('tableName.fieldName'));
        self::assertSame(
            'MAX("tableName"."fieldName") AS "anAlias"',
            $this->subject->max('tableName.fieldName', 'anAlias')
        );
    }

    /**
     * @test
     */
    public function minQuotesIdentifier()
    {
        $this->connectionProphet->quoteIdentifier(Argument::cetera())->will(function ($args) {
            $platform = new MockPlatform();
            return $platform->quoteIdentifier($args[0]);
        });

        self::assertSame('MIN("tableName"."fieldName")', $this->subject->min('tableName.fieldName'));
        self::assertSame(
            'MIN("tableName"."fieldName") AS "anAlias"',
            $this->subject->min('tableName.fieldName', 'anAlias')
        );
    }

    /**
     * @test
     */
    public function sumQuotesIdentifier()
    {
        $this->connectionProphet->quoteIdentifier(Argument::cetera())->will(function ($args) {
            $platform = new MockPlatform();
            return $platform->quoteIdentifier($args[0]);
        });

        self::assertSame('SUM("tableName"."fieldName")', $this->subject->sum('tableName.fieldName'));
        self::assertSame(
            'SUM("tableName"."fieldName") AS "anAlias"',
            $this->subject->sum('tableName.fieldName', 'anAlias')
        );
    }

    /**
     * @test
     */
    public function avgQuotesIdentifier()
    {
        $this->connectionProphet->quoteIdentifier(Argument::cetera())->will(function ($args) {
            $platform = new MockPlatform();
            return $platform->quoteIdentifier($args[0]);
        });

        self::assertSame('AVG("tableName"."fieldName")', $this->subject->avg('tableName.fieldName'));
        self::assertSame(
            'AVG("tableName"."fieldName") AS "anAlias"',
            $this->subject->avg('tableName.fieldName', 'anAlias')
        );
    }

    /**
     * @test
     */
    public function countQuotesIdentifier()
    {
        $this->connectionProphet->quoteIdentifier(Argument::cetera())->will(function ($args) {
            $platform = new MockPlatform();
            return $platform->quoteIdentifier($args[0]);
        });

        self::assertSame('COUNT("tableName"."fieldName")', $this->subject->count('tableName.fieldName'));
        self::assertSame(
            'COUNT("tableName"."fieldName") AS "anAlias"',
            $this->subject->count('tableName.fieldName', 'anAlias')
        );
    }

    /**
     * @test
     */
    public function lengthQuotesIdentifier()
    {
        $this->connectionProphet->quoteIdentifier(Argument::cetera())->will(function ($args) {
            $platform = new MockPlatform();
            return $platform->quoteIdentifier($args[0]);
        });

        self::assertSame('LENGTH("tableName"."fieldName")', $this->subject->length('tableName.fieldName'));
        self::assertSame(
            'LENGTH("tableName"."fieldName") AS "anAlias"',
            $this->subject->length('tableName.fieldName', 'anAlias')
        );
    }

    /**
     * @test
     */
    public function trimQuotesIdentifierWithDefaultValues()
    {
        $platform = new MockPlatform();
        $this->connectionProphet->getDatabasePlatform(Argument::cetera())
            ->shouldBeCalled()
            ->willReturn($platform);
        $this->connectionProphet->quoteIdentifier(Argument::cetera())
            ->shouldBeCalled()
            ->will(
                function ($args) use ($platform) {
                    return $platform->quoteIdentifier($args[0]);
                }
            );

        self::assertSame(
            'TRIM("tableName"."fieldName")',
            $this->subject->trim('tableName.fieldName')
        );
    }

    /**
     * @return array
     */
    public function trimQuotesIdentifierDataProvider()
    {
        return  [
            'trim leading character' => [
                AbstractPlatform::TRIM_LEADING,
                'x',
                'TRIM(LEADING "x" FROM "tableName"."fieldName")'
            ],
            'trim trailing character' => [
                AbstractPlatform::TRIM_TRAILING,
                'x',
                'TRIM(TRAILING "x" FROM "tableName"."fieldName")',
            ],
            'trim character' => [
                AbstractPlatform::TRIM_BOTH,
                'x',
                'TRIM(BOTH "x" FROM "tableName"."fieldName")',
            ],
            'trim space' => [
                AbstractPlatform::TRIM_BOTH,
                ' ',
                'TRIM(BOTH " " FROM "tableName"."fieldName")',
            ]
        ];
    }

    /**
     * @param int $position
     * @param string $char
     * @param string $expected
     *
     * @test
     * @dataProvider trimQuotesIdentifierDataProvider
     */
    public function trimQuotesIdentifier(int $position, string $char, string $expected)
    {
        $platform = new MockPlatform();
        $this->connectionProphet->getDatabasePlatform(Argument::cetera())
            ->shouldBeCalled()
            ->willReturn($platform);
        $this->connectionProphet->quoteIdentifier(Argument::cetera())
            ->shouldBeCalled()
            ->will(
                function ($args) use ($platform) {
                    return $platform->quoteIdentifier($args[0]);
                }
            );
        $this->connectionProphet->quote(Argument::cetera())
            ->shouldBeCalled()
            ->will(
                function ($args) {
                    return '"' . $args[0] . '"';
                }
            );

        self::assertSame(
            $expected,
            $this->subject->trim('tableName.fieldName', $position, $char)
        );
    }

    /**
     * @test
     */
    public function literalQuotesValue()
    {
        $this->connectionProphet->quote('aField', 'Doctrine\DBAL\Types\StringType')
            ->shouldBeCalled()
            ->willReturn('"aField"');
        $result = $this->subject->literal('aField', 'Doctrine\DBAL\Types\StringType');

        self::assertSame('"aField"', $result);
    }
}
