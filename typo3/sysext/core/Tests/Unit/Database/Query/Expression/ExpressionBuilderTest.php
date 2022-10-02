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

use Doctrine\DBAL\Platforms\TrimMode;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
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
    use ProphecyTrait;

    /** @var ObjectProphecy<Connection> */
    protected ObjectProphecy $connectionProphecy;

    protected ?ExpressionBuilder $subject;

    protected string $testTable = 'testTable';

    /**
     * Create a new database connection mock object for every test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->connectionProphecy = $this->prophesize(Connection::class);
        $this->connectionProphecy->quoteIdentifier(Argument::cetera())->willReturnArgument(0);

        $this->subject = new ExpressionBuilder($this->connectionProphecy->reveal());
    }

    /**
     * @test
     */
    public function andXReturnType(): void
    {
        $result = $this->subject->and('"uid" = 1', '"pid" = 0');

        self::assertInstanceOf(CompositeExpression::class, $result);
        self::assertSame(CompositeExpression::TYPE_AND, $result->getType());
    }

    /**
     * @test
     */
    public function orXReturnType(): void
    {
        $result = $this->subject->or('"uid" = 1', '"uid" = 7');

        self::assertInstanceOf(CompositeExpression::class, $result);
        self::assertSame(CompositeExpression::TYPE_OR, $result->getType());
    }

    /**
     * @test
     */
    public function eqQuotesIdentifier(): void
    {
        $result = $this->subject->eq('aField', 1);

        $this->connectionProphecy->quoteIdentifier('aField')->shouldHaveBeenCalled();
        self::assertSame('aField = 1', $result);
    }

    /**
     * @test
     */
    public function neqQuotesIdentifier(): void
    {
        $result = $this->subject->neq('aField', 1);

        $this->connectionProphecy->quoteIdentifier('aField')->shouldHaveBeenCalled();
        self::assertSame('aField <> 1', $result);
    }

    /**
     * @test
     */
    public function ltQuotesIdentifier(): void
    {
        $result = $this->subject->lt('aField', 1);

        $this->connectionProphecy->quoteIdentifier('aField')->shouldHaveBeenCalled();
        self::assertSame('aField < 1', $result);
    }

    /**
     * @test
     */
    public function lteQuotesIdentifier(): void
    {
        $result = $this->subject->lte('aField', 1);

        $this->connectionProphecy->quoteIdentifier('aField')->shouldHaveBeenCalled();
        self::assertSame('aField <= 1', $result);
    }

    /**
     * @test
     */
    public function gtQuotesIdentifier(): void
    {
        $result = $this->subject->gt('aField', 1);

        $this->connectionProphecy->quoteIdentifier('aField')->shouldHaveBeenCalled();
        self::assertSame('aField > 1', $result);
    }

    /**
     * @test
     */
    public function gteQuotesIdentifier(): void
    {
        $result = $this->subject->gte('aField', 1);

        $this->connectionProphecy->quoteIdentifier('aField')->shouldHaveBeenCalled();
        self::assertSame('aField >= 1', $result);
    }

    /**
     * @test
     */
    public function isNullQuotesIdentifier(): void
    {
        $result = $this->subject->isNull('aField');

        $this->connectionProphecy->quoteIdentifier('aField')->shouldHaveBeenCalled();
        self::assertSame('aField IS NULL', $result);
    }

    /**
     * @test
     */
    public function isNotNullQuotesIdentifier(): void
    {
        $result = $this->subject->isNotNull('aField');

        $this->connectionProphecy->quoteIdentifier('aField')->shouldHaveBeenCalled();
        self::assertSame('aField IS NOT NULL', $result);
    }

    /**
     * @test
     */
    public function likeQuotesIdentifier(): void
    {
        $result = $this->subject->like('aField', "'aValue%'");

        $this->connectionProphecy->quoteIdentifier('aField')->shouldHaveBeenCalled();
        self::assertSame("aField LIKE 'aValue%'", $result);
    }

    /**
     * @test
     */
    public function notLikeQuotesIdentifier(): void
    {
        $result = $this->subject->notLike('aField', "'aValue%'");

        $this->connectionProphecy->quoteIdentifier('aField')->shouldHaveBeenCalled();
        self::assertSame("aField NOT LIKE 'aValue%'", $result);
    }

    /**
     * @test
     */
    public function inWithStringQuotesIdentifier(): void
    {
        $result = $this->subject->in('aField', '1,2,3');

        $this->connectionProphecy->quoteIdentifier('aField')->shouldHaveBeenCalled();
        self::assertSame('aField IN (1,2,3)', $result);
    }

    /**
     * @test
     */
    public function inWithArrayQuotesIdentifier(): void
    {
        $result = $this->subject->in('aField', [1, 2, 3]);

        $this->connectionProphecy->quoteIdentifier('aField')->shouldHaveBeenCalled();
        self::assertSame('aField IN (1, 2, 3)', $result);
    }

    /**
     * @test
     */
    public function notInWithStringQuotesIdentifier(): void
    {
        $result = $this->subject->notIn('aField', '1,2,3');

        $this->connectionProphecy->quoteIdentifier('aField')->shouldHaveBeenCalled();
        self::assertSame('aField NOT IN (1,2,3)', $result);
    }

    /**
     * @test
     */
    public function notInWithArrayQuotesIdentifier(): void
    {
        $result = $this->subject->notIn('aField', [1, 2, 3]);

        $this->connectionProphecy->quoteIdentifier('aField')->shouldHaveBeenCalled();
        self::assertSame('aField NOT IN (1, 2, 3)', $result);
    }

    /**
     * @test
     */
    public function inSetThrowsExceptionWithEmptyValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1459696089);
        $this->subject->inSet('aField', '');
    }

    /**
     * @test
     */
    public function inSetThrowsExceptionWithInvalidValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1459696090);
        $this->subject->inSet('aField', 'an,Invalid,Value');
    }

    /**
     * @test
     */
    public function inSetForMySQL(): void
    {
        $databasePlatform = $this->prophesize(MockPlatform::class);
        $databasePlatform->getName()->willReturn('mysql');

        $this->connectionProphecy->quoteIdentifier(Argument::cetera())->will(static function ($args) {
            return '`' . $args[0] . '`';
        });

        $this->connectionProphecy->getDatabasePlatform()->willReturn($databasePlatform->reveal());

        $result = $this->subject->inSet('aField', "'1'");

        self::assertSame('FIND_IN_SET(\'1\', `aField`)', $result);
    }

    /**
     * @test
     */
    public function inSetForPostgreSQL(): void
    {
        $databasePlatform = $this->prophesize(MockPlatform::class);
        $databasePlatform->getName()->willReturn('postgresql');
        $databasePlatform->getStringLiteralQuoteCharacter()->willReturn('"');

        $this->connectionProphecy->quote(',', Argument::cetera())->shouldBeCalled()->willReturn("','");
        $this->connectionProphecy->quote("'1'", \PDO::PARAM_STR)->shouldBeCalled()->willReturn("'1'");
        $this->connectionProphecy->quoteIdentifier(Argument::cetera())->will(static function ($args) {
            return '"' . $args[0] . '"';
        });

        $this->connectionProphecy->getDatabasePlatform()->willReturn($databasePlatform->reveal());

        $result = $this->subject->inSet('aField', "'1'");

        self::assertSame('\'1\' = ANY(string_to_array("aField"::text, \',\'))', $result);
    }

    /**
     * @test
     */
    public function inSetForPostgreSQLWithColumn(): void
    {
        $databasePlatform = $this->prophesize(MockPlatform::class);
        $databasePlatform->getName()->willReturn('postgresql');

        $this->connectionProphecy->quote(',', Argument::cetera())->shouldBeCalled()->willReturn("','");
        $this->connectionProphecy->quoteIdentifier(Argument::cetera())->will(static function ($args) {
            return '"' . $args[0] . '"';
        });

        $this->connectionProphecy->getDatabasePlatform()->willReturn($databasePlatform->reveal());

        $result = $this->subject->inSet('aField', '"testtable"."uid"', true);

        self::assertSame('"testtable"."uid"::text = ANY(string_to_array("aField"::text, \',\'))', $result);
    }

    /**
     * @test
     */
    public function inSetForSQLite(): void
    {
        $databasePlatform = $this->prophesize(MockPlatform::class);
        $databasePlatform->getName()->willReturn('sqlite');
        $databasePlatform->getStringLiteralQuoteCharacter()->willReturn("'");

        $this->connectionProphecy->quote(',', Argument::cetera())->shouldBeCalled()->willReturn("','");
        $this->connectionProphecy->quote(',1,', Argument::cetera())->shouldBeCalled()->willReturn("'%,1,%'");
        $this->connectionProphecy->quoteIdentifier(Argument::cetera())->will(static function ($args) {
            return '"' . $args[0] . '"';
        });

        $this->connectionProphecy->getDatabasePlatform()->willReturn($databasePlatform->reveal());

        $result = $this->subject->inSet('aField', "'1'");

        self::assertSame('instr(\',\'||"aField"||\',\', \'%,1,%\')', $result);
    }

    /**
     * @test
     */
    public function inSetForSQLiteWithQuoteCharactersInValue(): void
    {
        $databasePlatform = $this->prophesize(MockPlatform::class);
        $databasePlatform->getName()->willReturn('sqlite');
        $databasePlatform->getStringLiteralQuoteCharacter()->willReturn("'");

        $this->connectionProphecy->quote(',', Argument::cetera())->shouldBeCalled()->willReturn("','");
        $this->connectionProphecy->quote(',\'Some\'Value,', Argument::cetera())->shouldBeCalled()
            ->willReturn("',''Some''Value,'");
        $this->connectionProphecy->quoteIdentifier(Argument::cetera())->will(static function ($args) {
            return '"' . $args[0] . '"';
        });

        $this->connectionProphecy->getDatabasePlatform()->willReturn($databasePlatform->reveal());

        $result = $this->subject->inSet('aField', "'''Some''Value'");

        self::assertSame('instr(\',\'||"aField"||\',\', \',\'\'Some\'\'Value,\')', $result);
    }

    /**
     * @test
     */
    public function inSetForSQLiteThrowsExceptionOnPositionalPlaceholder(): void
    {
        $databasePlatform = $this->prophesize(MockPlatform::class);
        $databasePlatform->getName()->willReturn('sqlite');
        $databasePlatform->getStringLiteralQuoteCharacter()->willReturn("'");

        $this->connectionProphecy->getDatabasePlatform()->willReturn($databasePlatform->reveal());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1476029421);

        $this->subject->inSet('aField', '?');
    }

    /**
     * @test
     */
    public function inSetForSQLiteThrowsExceptionOnNamedPlaceholder(): void
    {
        $databasePlatform = $this->prophesize(MockPlatform::class);
        $databasePlatform->getName()->willReturn('sqlite');
        $databasePlatform->getStringLiteralQuoteCharacter()->willReturn("'");

        $this->connectionProphecy->getDatabasePlatform()->willReturn($databasePlatform->reveal());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1476029421);

        $this->subject->inSet('aField', ':dcValue1');
    }

    /**
     * @test
     */
    public function notInSetThrowsExceptionWithEmptyValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1627573099);
        $this->subject->notInSet('aField', '');
    }

    /**
     * @test
     */
    public function notInSetThrowsExceptionWithInvalidValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1627573100);
        $this->subject->notInSet('aField', 'an,Invalid,Value');
    }

    /**
     * @test
     */
    public function notInSetForMySQL(): void
    {
        $databasePlatform = $this->prophesize(MockPlatform::class);
        $databasePlatform->getName()->willReturn('mysql');

        $this->connectionProphecy->quoteIdentifier(Argument::cetera())->will(static function ($args) {
            return '`' . $args[0] . '`';
        });

        $this->connectionProphecy->getDatabasePlatform()->willReturn($databasePlatform->reveal());

        $result = $this->subject->notInSet('aField', "'1'");

        self::assertSame('NOT FIND_IN_SET(\'1\', `aField`)', $result);
    }

    /**
     * @test
     */
    public function notInSetForPostgreSQL(): void
    {
        $databasePlatform = $this->prophesize(MockPlatform::class);
        $databasePlatform->getName()->willReturn('postgresql');
        $databasePlatform->getStringLiteralQuoteCharacter()->willReturn('"');

        $this->connectionProphecy->quote(',', Argument::cetera())->shouldBeCalled()->willReturn("','");
        $this->connectionProphecy->quote("'1'", \PDO::PARAM_STR)->shouldBeCalled()->willReturn("'1'");
        $this->connectionProphecy->quoteIdentifier(Argument::cetera())->will(static function ($args) {
            return '"' . $args[0] . '"';
        });

        $this->connectionProphecy->getDatabasePlatform()->willReturn($databasePlatform->reveal());

        $result = $this->subject->notInSet('aField', "'1'");

        self::assertSame('\'1\' <> ALL(string_to_array("aField"::text, \',\'))', $result);
    }

    /**
     * @test
     */
    public function notInSetForPostgreSQLWithColumn(): void
    {
        $databasePlatform = $this->prophesize(MockPlatform::class);
        $databasePlatform->getName()->willReturn('postgresql');

        $this->connectionProphecy->quote(',', Argument::cetera())->shouldBeCalled()->willReturn("','");
        $this->connectionProphecy->quoteIdentifier(Argument::cetera())->will(static function ($args) {
            return '"' . $args[0] . '"';
        });

        $this->connectionProphecy->getDatabasePlatform()->willReturn($databasePlatform->reveal());

        $result = $this->subject->notInSet('aField', '"testtable"."uid"', true);

        self::assertSame('"testtable"."uid"::text <> ALL(string_to_array("aField"::text, \',\'))', $result);
    }

    /**
     * @test
     */
    public function notInSetForSQLite(): void
    {
        $databasePlatform = $this->prophesize(MockPlatform::class);
        $databasePlatform->getName()->willReturn('sqlite');
        $databasePlatform->getStringLiteralQuoteCharacter()->willReturn("'");

        $this->connectionProphecy->quote(',', Argument::cetera())->shouldBeCalled()->willReturn("','");
        $this->connectionProphecy->quote(',1,', Argument::cetera())->shouldBeCalled()->willReturn("'%,1,%'");
        $this->connectionProphecy->quoteIdentifier(Argument::cetera())->will(static function ($args) {
            return '"' . $args[0] . '"';
        });

        $this->connectionProphecy->getDatabasePlatform()->willReturn($databasePlatform->reveal());

        $result = $this->subject->notInSet('aField', "'1'");

        self::assertSame('instr(\',\'||"aField"||\',\', \'%,1,%\') = 0', $result);
    }

    /**
     * @test
     */
    public function notInSetForSQLiteWithQuoteCharactersInValue(): void
    {
        $databasePlatform = $this->prophesize(MockPlatform::class);
        $databasePlatform->getName()->willReturn('sqlite');
        $databasePlatform->getStringLiteralQuoteCharacter()->willReturn("'");

        $this->connectionProphecy->quote(',', Argument::cetera())->shouldBeCalled()->willReturn("','");
        $this->connectionProphecy->quote(',\'Some\'Value,', Argument::cetera())->shouldBeCalled()
            ->willReturn("',''Some''Value,'");
        $this->connectionProphecy->quoteIdentifier(Argument::cetera())->will(static function ($args) {
            return '"' . $args[0] . '"';
        });

        $this->connectionProphecy->getDatabasePlatform()->willReturn($databasePlatform->reveal());

        $result = $this->subject->notInSet('aField', "'''Some''Value'");

        self::assertSame('instr(\',\'||"aField"||\',\', \',\'\'Some\'\'Value,\') = 0', $result);
    }

    /**
     * @test
     */
    public function notInSetForSQLiteThrowsExceptionOnPositionalPlaceholder(): void
    {
        $databasePlatform = $this->prophesize(MockPlatform::class);
        $databasePlatform->getName()->willReturn('sqlite');
        $databasePlatform->getStringLiteralQuoteCharacter()->willReturn("'");

        $this->connectionProphecy->getDatabasePlatform()->willReturn($databasePlatform->reveal());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1627573103);

        $this->subject->notInSet('aField', '?');
    }

    /**
     * @test
     */
    public function notInSetForSQLiteThrowsExceptionOnNamedPlaceholder(): void
    {
        $databasePlatform = $this->prophesize(MockPlatform::class);
        $databasePlatform->getName()->willReturn('sqlite');
        $databasePlatform->getStringLiteralQuoteCharacter()->willReturn("'");

        $this->connectionProphecy->getDatabasePlatform()->willReturn($databasePlatform->reveal());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1476029421);

        $this->subject->inSet('aField', ':dcValue1');
    }

    /**
     * @test
     */
    public function defaultBitwiseAnd(): void
    {
        $databasePlatform = $this->prophesize(MockPlatform::class);

        $this->connectionProphecy->quoteIdentifier(Argument::cetera())->will(static function ($args) {
            return '"' . $args[0] . '"';
        });

        $this->connectionProphecy->getDatabasePlatform()->willReturn($databasePlatform->reveal());

        self::assertSame('"aField" & 1', $this->subject->bitAnd('aField', 1));
    }

    /**
     * @test
     */
    public function bitwiseAndForOracle(): void
    {
        $databasePlatform = $this->prophesize(MockPlatform::class);
        $databasePlatform->getName()->willReturn('pdo_oracle');

        $this->connectionProphecy->quoteIdentifier(Argument::cetera())->will(static function ($args) {
            return '"' . $args[0] . '"';
        });

        $this->connectionProphecy->getDatabasePlatform()->willReturn($databasePlatform->reveal());

        self::assertSame('BITAND("aField", 1)', $this->subject->bitAnd('aField', 1));
    }

    /**
     * @test
     */
    public function maxQuotesIdentifier(): void
    {
        $this->connectionProphecy->quoteIdentifier(Argument::cetera())->will(static function ($args) {
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
    public function minQuotesIdentifier(): void
    {
        $this->connectionProphecy->quoteIdentifier(Argument::cetera())->will(static function ($args) {
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
    public function sumQuotesIdentifier(): void
    {
        $this->connectionProphecy->quoteIdentifier(Argument::cetera())->will(static function ($args) {
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
    public function avgQuotesIdentifier(): void
    {
        $this->connectionProphecy->quoteIdentifier(Argument::cetera())->will(static function ($args) {
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
    public function countQuotesIdentifier(): void
    {
        $this->connectionProphecy->quoteIdentifier(Argument::cetera())->will(static function ($args) {
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
    public function lengthQuotesIdentifier(): void
    {
        $this->connectionProphecy->quoteIdentifier(Argument::cetera())->will(static function ($args) {
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
    public function trimQuotesIdentifierWithDefaultValues(): void
    {
        $platform = new MockPlatform();
        $this->connectionProphecy->getDatabasePlatform(Argument::cetera())
            ->shouldBeCalled()
            ->willReturn($platform);
        $this->connectionProphecy->quoteIdentifier(Argument::cetera())
            ->shouldBeCalled()
            ->will(
                static function ($args) use ($platform) {
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
    public function trimQuotesIdentifierDataProvider(): array
    {
        return  [
            'trim leading character' => [
                TrimMode::LEADING,
                'x',
                'TRIM(LEADING "x" FROM "tableName"."fieldName")',
            ],
            'trim trailing character' => [
                TrimMode::TRAILING,
                'x',
                'TRIM(TRAILING "x" FROM "tableName"."fieldName")',
            ],
            'trim character' => [
                TrimMode::BOTH,
                'x',
                'TRIM(BOTH "x" FROM "tableName"."fieldName")',
            ],
            'trim space' => [
                TrimMode::BOTH,
                ' ',
                'TRIM(BOTH " " FROM "tableName"."fieldName")',
            ],
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
    public function trimQuotesIdentifier(int $position, string $char, string $expected): void
    {
        $platform = new MockPlatform();
        $this->connectionProphecy->getDatabasePlatform(Argument::cetera())
            ->shouldBeCalled()
            ->willReturn($platform);
        $this->connectionProphecy->quoteIdentifier(Argument::cetera())
            ->shouldBeCalled()
            ->will(
                static function ($args) use ($platform) {
                    return $platform->quoteIdentifier($args[0]);
                }
            );
        $this->connectionProphecy->quote(Argument::cetera())
            ->shouldBeCalled()
            ->will(
                static function ($args) {
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
    public function literalQuotesValue(): void
    {
        $this->connectionProphecy->quote('aField', \PDO::PARAM_STR)
            ->shouldBeCalled()
            ->willReturn('"aField"');
        $result = $this->subject->literal('aField');

        self::assertSame('"aField"', $result);
    }
}
