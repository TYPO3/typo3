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
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ExpressionBuilderTest extends UnitTestCase
{
    protected Connection&MockObject $connectionMock;
    protected ?ExpressionBuilder $subject;
    protected string $testTable = 'testTable';

    /**
     * Create a new database connection mock object for every test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->connectionMock = $this->createMock(Connection::class);
        $this->subject = new ExpressionBuilder($this->connectionMock);
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
        $this->connectionMock->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $result = $this->subject->eq('aField', 1);

        self::assertSame('aField = 1', $result);
    }

    /**
     * @test
     */
    public function neqQuotesIdentifier(): void
    {
        $this->connectionMock->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $result = $this->subject->neq('aField', 1);

        self::assertSame('aField <> 1', $result);
    }

    /**
     * @test
     */
    public function ltQuotesIdentifier(): void
    {
        $this->connectionMock->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $result = $this->subject->lt('aField', 1);

        self::assertSame('aField < 1', $result);
    }

    /**
     * @test
     */
    public function lteQuotesIdentifier(): void
    {
        $this->connectionMock->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $result = $this->subject->lte('aField', 1);

        self::assertSame('aField <= 1', $result);
    }

    /**
     * @test
     */
    public function gtQuotesIdentifier(): void
    {
        $this->connectionMock->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $result = $this->subject->gt('aField', 1);

        self::assertSame('aField > 1', $result);
    }

    /**
     * @test
     */
    public function gteQuotesIdentifier(): void
    {
        $this->connectionMock->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $result = $this->subject->gte('aField', 1);

        self::assertSame('aField >= 1', $result);
    }

    /**
     * @test
     */
    public function isNullQuotesIdentifier(): void
    {
        $this->connectionMock->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $result = $this->subject->isNull('aField');

        self::assertSame('aField IS NULL', $result);
    }

    /**
     * @test
     */
    public function isNotNullQuotesIdentifier(): void
    {
        $this->connectionMock->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $result = $this->subject->isNotNull('aField');

        self::assertSame('aField IS NOT NULL', $result);
    }

    /**
     * @test
     */
    public function likeQuotesIdentifier(): void
    {
        $databasePlatform = $this->createMock(MockPlatform::class);
        $databasePlatform->method('getName')->willReturn('mysql');
        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);
        $this->connectionMock->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $result = $this->subject->like('aField', "'aValue%'");
        self::assertSame("aField LIKE 'aValue%'", $result);
    }

    /**
     * @test
     */
    public function notLikeQuotesIdentifier(): void
    {
        $databasePlatform = $this->createMock(MockPlatform::class);
        $databasePlatform->method('getName')->willReturn('mysql');
        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);
        $this->connectionMock->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $result = $this->subject->notLike('aField', "'aValue%'");
        self::assertSame("aField NOT LIKE 'aValue%'", $result);
    }

    /**
     * @test
     */
    public function inWithStringQuotesIdentifier(): void
    {
        $this->connectionMock->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $result = $this->subject->in('aField', '1,2,3');

        self::assertSame('aField IN (1,2,3)', $result);
    }

    /**
     * @test
     */
    public function inWithArrayQuotesIdentifier(): void
    {
        $this->connectionMock->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $result = $this->subject->in('aField', [1, 2, 3]);

        self::assertSame('aField IN (1, 2, 3)', $result);
    }

    /**
     * @test
     */
    public function notInWithStringQuotesIdentifier(): void
    {
        $this->connectionMock->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $result = $this->subject->notIn('aField', '1,2,3');

        self::assertSame('aField NOT IN (1,2,3)', $result);
    }

    /**
     * @test
     */
    public function notInWithArrayQuotesIdentifier(): void
    {
        $this->connectionMock->expects(self::atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $result = $this->subject->notIn('aField', [1, 2, 3]);

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
        $databasePlatform = $this->createMock(MockPlatform::class);
        $databasePlatform->method('getName')->willReturn('mysql');

        $this->connectionMock->method('quoteIdentifier')->with(self::anything())->willReturnCallback(static function ($args) {
            return '`' . $args . '`';
        });

        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        $result = $this->subject->inSet('aField', "'1'");

        self::assertSame('FIND_IN_SET(\'1\', `aField`)', $result);
    }

    /**
     * @test
     */
    public function inSetForPostgreSQL(): void
    {
        $databasePlatform = $this->createMock(MockPlatform::class);
        $databasePlatform->method('getName')->willReturn('postgresql');
        $databasePlatform->method('getStringLiteralQuoteCharacter')->willReturn('"');

        $this->connectionMock->expects(self::atLeastOnce())->method('quote')->withConsecutive(
            ["'1'", Connection::PARAM_STR],
            [',', self::anything()],
        )->willReturnOnConsecutiveCalls("'1'", "','");
        $this->connectionMock->method('quoteIdentifier')->with(self::anything())->willReturnCallback(static function ($args) {
            return '"' . $args . '"';
        });

        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        $result = $this->subject->inSet('aField', "'1'");

        self::assertSame('\'1\' = ANY(string_to_array("aField"::text, \',\'))', $result);
    }

    /**
     * @test
     */
    public function inSetForPostgreSQLWithColumn(): void
    {
        $databasePlatform = $this->createMock(MockPlatform::class);
        $databasePlatform->method('getName')->willReturn('postgresql');

        $this->connectionMock->expects(self::atLeastOnce())->method('quote')->with(',', self::anything())->willReturn("','");
        $this->connectionMock->method('quoteIdentifier')->with(self::anything())->willReturnCallback(static function ($args) {
            return '"' . $args . '"';
        });

        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        $result = $this->subject->inSet('aField', '"testtable"."uid"', true);

        self::assertSame('"testtable"."uid"::text = ANY(string_to_array("aField"::text, \',\'))', $result);
    }

    /**
     * @test
     */
    public function inSetForSQLite(): void
    {
        $databasePlatform = $this->createMock(MockPlatform::class);
        $databasePlatform->method('getName')->willReturn('sqlite');
        $databasePlatform->method('getStringLiteralQuoteCharacter')->willReturn("'");

        $this->connectionMock->expects(self::atLeastOnce())->method('quote')->withConsecutive(
            [',', self::anything()],
            [',', self::anything()],
            [',1,', self::anything()],
        )->willReturnOnConsecutiveCalls("','", "','", "'%,1,%'");
        $this->connectionMock->method('quoteIdentifier')->with(self::anything())->willReturnCallback(static function ($args) {
            return '"' . $args . '"';
        });

        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        $result = $this->subject->inSet('aField', "'1'");

        self::assertSame('instr(\',\'||"aField"||\',\', \'%,1,%\')', $result);
    }

    /**
     * @test
     */
    public function inSetForSQLiteWithQuoteCharactersInValue(): void
    {
        $databasePlatform = $this->createMock(MockPlatform::class);
        $databasePlatform->method('getName')->willReturn('sqlite');
        $databasePlatform->method('getStringLiteralQuoteCharacter')->willReturn("'");

        $this->connectionMock->expects(self::atLeastOnce())->method('quote')->withConsecutive(
            [',', self::anything()],
            [',', self::anything()],
            [',\'Some\'Value,', self::anything()],
        )->willReturnOnConsecutiveCalls("','", "','", "',''Some''Value,'");
        $this->connectionMock->method('quoteIdentifier')->with(self::anything())->willReturnCallback(static function ($args) {
            return '"' . $args . '"';
        });

        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        $result = $this->subject->inSet('aField', "'''Some''Value'");

        self::assertSame('instr(\',\'||"aField"||\',\', \',\'\'Some\'\'Value,\')', $result);
    }

    /**
     * @test
     */
    public function inSetForSQLiteThrowsExceptionOnPositionalPlaceholder(): void
    {
        $databasePlatform = $this->createMock(MockPlatform::class);
        $databasePlatform->method('getName')->willReturn('sqlite');
        $databasePlatform->method('getStringLiteralQuoteCharacter')->willReturn("'");

        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1476029421);

        $this->subject->inSet('aField', '?');
    }

    /**
     * @test
     */
    public function inSetForSQLiteThrowsExceptionOnNamedPlaceholder(): void
    {
        $databasePlatform = $this->createMock(MockPlatform::class);
        $databasePlatform->method('getName')->willReturn('sqlite');
        $databasePlatform->method('getStringLiteralQuoteCharacter')->willReturn("'");

        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

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
        $databasePlatform = $this->createMock(MockPlatform::class);
        $databasePlatform->method('getName')->willReturn('mysql');

        $this->connectionMock->method('quoteIdentifier')->with(self::anything())->willReturnCallback(static function ($args) {
            return '`' . $args . '`';
        });

        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        $result = $this->subject->notInSet('aField', "'1'");

        self::assertSame('NOT FIND_IN_SET(\'1\', `aField`)', $result);
    }

    /**
     * @test
     */
    public function notInSetForPostgreSQL(): void
    {
        $databasePlatform = $this->createMock(MockPlatform::class);
        $databasePlatform->method('getName')->willReturn('postgresql');
        $databasePlatform->method('getStringLiteralQuoteCharacter')->willReturn('"');

        $this->connectionMock->expects(self::atLeastOnce())->method('quote')->withConsecutive(
            ["'1'", Connection::PARAM_STR],
            [',', self::anything()],
        )->willReturnOnConsecutiveCalls("'1'", "','");
        $this->connectionMock->method('quoteIdentifier')->with(self::anything())->willReturnCallback(static function ($args) {
            return '"' . $args . '"';
        });

        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        $result = $this->subject->notInSet('aField', "'1'");

        self::assertSame('\'1\' <> ALL(string_to_array("aField"::text, \',\'))', $result);
    }

    /**
     * @test
     */
    public function notInSetForPostgreSQLWithColumn(): void
    {
        $databasePlatform = $this->createMock(MockPlatform::class);
        $databasePlatform->method('getName')->willReturn('postgresql');

        $this->connectionMock->expects(self::atLeastOnce())->method('quote')->with(',', self::anything())->willReturn("','");
        $this->connectionMock->method('quoteIdentifier')->with(self::anything())->willReturnCallback(static function ($args) {
            return '"' . $args . '"';
        });

        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        $result = $this->subject->notInSet('aField', '"testtable"."uid"', true);

        self::assertSame('"testtable"."uid"::text <> ALL(string_to_array("aField"::text, \',\'))', $result);
    }

    /**
     * @test
     */
    public function notInSetForSQLite(): void
    {
        $databasePlatform = $this->createMock(MockPlatform::class);
        $databasePlatform->method('getName')->willReturn('sqlite');
        $databasePlatform->method('getStringLiteralQuoteCharacter')->willReturn("'");

        $this->connectionMock->expects(self::atLeastOnce())->method('quote')->withConsecutive(
            [',', self::anything()],
            [',', self::anything()],
            [',1,', self::anything()]
        )->willReturnOnConsecutiveCalls("','", "','", "'%,1,%'");
        $this->connectionMock->method('quoteIdentifier')->with(self::anything())->willReturnCallback(static function ($args) {
            return '"' . $args . '"';
        });

        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        $result = $this->subject->notInSet('aField', "'1'");

        self::assertSame('instr(\',\'||"aField"||\',\', \'%,1,%\') = 0', $result);
    }

    /**
     * @test
     */
    public function notInSetForSQLiteWithQuoteCharactersInValue(): void
    {
        $databasePlatform = $this->createMock(MockPlatform::class);
        $databasePlatform->method('getName')->willReturn('sqlite');
        $databasePlatform->method('getStringLiteralQuoteCharacter')->willReturn("'");

        $this->connectionMock->expects(self::atLeastOnce())->method('quote')->withConsecutive(
            [',', Connection::PARAM_STR],
            [',', Connection::PARAM_STR],
            [',\'Some\'Value,', self::anything()],
        )->willReturnOnConsecutiveCalls("','", "','", "',''Some''Value,'");
        $this->connectionMock->method('quoteIdentifier')->with(self::anything())->willReturnCallback(static function ($args) {
            return '"' . $args . '"';
        });

        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        $result = $this->subject->notInSet('aField', "'''Some''Value'");

        self::assertSame('instr(\',\'||"aField"||\',\', \',\'\'Some\'\'Value,\') = 0', $result);
    }

    /**
     * @test
     */
    public function notInSetForSQLiteThrowsExceptionOnPositionalPlaceholder(): void
    {
        $databasePlatform = $this->createMock(MockPlatform::class);
        $databasePlatform->method('getName')->willReturn('sqlite');
        $databasePlatform->method('getStringLiteralQuoteCharacter')->willReturn("'");

        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1627573103);

        $this->subject->notInSet('aField', '?');
    }

    /**
     * @test
     */
    public function notInSetForSQLiteThrowsExceptionOnNamedPlaceholder(): void
    {
        $databasePlatform = $this->createMock(MockPlatform::class);
        $databasePlatform->method('getName')->willReturn('sqlite');
        $databasePlatform->method('getStringLiteralQuoteCharacter')->willReturn("'");

        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1476029421);

        $this->subject->inSet('aField', ':dcValue1');
    }

    /**
     * @test
     */
    public function defaultBitwiseAnd(): void
    {
        $databasePlatform = $this->createMock(MockPlatform::class);

        $this->connectionMock->method('quoteIdentifier')->with(self::anything())->willReturnCallback(static function ($args) {
            return '"' . $args . '"';
        });

        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        self::assertSame('"aField" & 1', $this->subject->bitAnd('aField', 1));
    }

    /**
     * @test
     */
    public function bitwiseAndForOracle(): void
    {
        $databasePlatform = $this->createMock(MockPlatform::class);
        $databasePlatform->method('getName')->willReturn('pdo_oracle');

        $this->connectionMock->method('quoteIdentifier')->with(self::anything())->willReturnCallback(static function ($args) {
            return '"' . $args . '"';
        });

        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        self::assertSame('BITAND("aField", 1)', $this->subject->bitAnd('aField', 1));
    }

    /**
     * @test
     */
    public function maxQuotesIdentifier(): void
    {
        $this->connectionMock->method('quoteIdentifier')->with(self::anything())->willReturnCallback(static function ($args) {
            return (new MockPlatform())->quoteIdentifier($args);
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
        $this->connectionMock->method('quoteIdentifier')->with(self::anything())->willReturnCallback(static function ($args) {
            return (new MockPlatform())->quoteIdentifier($args);
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
        $this->connectionMock->method('quoteIdentifier')->with(self::anything())->willReturnCallback(static function ($args) {
            return (new MockPlatform())->quoteIdentifier($args);
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
        $this->connectionMock->method('quoteIdentifier')->with(self::anything())->willReturnCallback(static function ($args) {
            return (new MockPlatform())->quoteIdentifier($args);
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
        $this->connectionMock->method('quoteIdentifier')->with(self::anything())->willReturnCallback(static function ($args) {
            return (new MockPlatform())->quoteIdentifier($args);
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
        $this->connectionMock->method('quoteIdentifier')->with(self::anything())->willReturnCallback(static function ($args) {
            return (new MockPlatform())->quoteIdentifier($args);
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
        $this->connectionMock->expects(self::atLeastOnce())->method('getDatabasePlatform')->willReturn($platform);
        $this->connectionMock->expects(self::atLeastOnce())->method('quoteIdentifier')->with(self::anything())
            ->willReturnCallback(
                static function ($args) use ($platform) {
                    return $platform->quoteIdentifier($args);
                }
            );

        self::assertSame(
            'TRIM("tableName"."fieldName")',
            $this->subject->trim('tableName.fieldName')
        );
    }

    public static function trimQuotesIdentifierDataProvider(): array
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
     * @test
     * @dataProvider trimQuotesIdentifierDataProvider
     */
    public function trimQuotesIdentifier(int $position, string $char, string $expected): void
    {
        $platform = new MockPlatform();
        $this->connectionMock->expects(self::atLeastOnce())->method('getDatabasePlatform')->willReturn($platform);
        $this->connectionMock->expects(self::atLeastOnce())->method('quoteIdentifier')->with(self::anything())
            ->willReturnCallback(
                static function ($args) use ($platform) {
                    return $platform->quoteIdentifier($args);
                }
            );
        $this->connectionMock->expects(self::atLeastOnce())->method('quote')->willReturnCallback(
            static function ($args) {
                return '"' . $args . '"';
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
        $this->connectionMock->expects(self::atLeastOnce())->method('quote')->with('aField', Connection::PARAM_STR)
            ->willReturn('"aField"');
        $result = $this->subject->literal('aField');

        self::assertSame('"aField"', $result);
    }
}
