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
use Doctrine\DBAL\Platforms\MariaDBPlatform as DoctrineMariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform as DoctrineMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform as DoctrinePostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform as DoctrineSQLitePlatform;
use Doctrine\DBAL\Platforms\TrimMode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform\MockMySQLPlatform;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform\MockPlatform;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform\MockPostgreSQLPlatform;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform\MockSQLitePlatform;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ExpressionBuilderTest extends UnitTestCase
{
    private Connection&MockObject $connectionMock;
    private ExpressionBuilder $subject;

    /**
     * Create a new database connection mock object for every test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->connectionMock = $this->createMock(Connection::class);
        $this->subject = new ExpressionBuilder($this->connectionMock);
    }

    #[Test]
    public function andXReturnType(): void
    {
        $result = $this->subject->and('"uid" = 1', '"pid" = 0');
        self::assertSame(CompositeExpression::TYPE_AND, $result->getType());
    }

    #[Test]
    public function orXReturnType(): void
    {
        $result = $this->subject->or('"uid" = 1', '"uid" = 7');
        self::assertSame(CompositeExpression::TYPE_OR, $result->getType());
    }

    #[Test]
    public function eqQuotesIdentifier(): void
    {
        $this->connectionMock->expects($this->atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $result = $this->subject->eq('aField', 1);

        self::assertSame('aField = 1', $result);
    }

    #[Test]
    public function neqQuotesIdentifier(): void
    {
        $this->connectionMock->expects($this->atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $result = $this->subject->neq('aField', 1);

        self::assertSame('aField <> 1', $result);
    }

    #[Test]
    public function ltQuotesIdentifier(): void
    {
        $this->connectionMock->expects($this->atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $result = $this->subject->lt('aField', 1);

        self::assertSame('aField < 1', $result);
    }

    #[Test]
    public function lteQuotesIdentifier(): void
    {
        $this->connectionMock->expects($this->atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $result = $this->subject->lte('aField', 1);

        self::assertSame('aField <= 1', $result);
    }

    #[Test]
    public function gtQuotesIdentifier(): void
    {
        $this->connectionMock->expects($this->atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $result = $this->subject->gt('aField', 1);

        self::assertSame('aField > 1', $result);
    }

    #[Test]
    public function gteQuotesIdentifier(): void
    {
        $this->connectionMock->expects($this->atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $result = $this->subject->gte('aField', 1);

        self::assertSame('aField >= 1', $result);
    }

    #[Test]
    public function isNullQuotesIdentifier(): void
    {
        $this->connectionMock->expects($this->atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $result = $this->subject->isNull('aField');

        self::assertSame('aField IS NULL', $result);
    }

    #[Test]
    public function isNotNullQuotesIdentifier(): void
    {
        $this->connectionMock->expects($this->atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $result = $this->subject->isNotNull('aField');

        self::assertSame('aField IS NOT NULL', $result);
    }

    #[Test]
    public function likeQuotesLiteral(): void
    {
        $databasePlatform = $this->createMock(MockMySQLPlatform::class);
        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);
        $this->connectionMock->expects($this->atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $this->connectionMock->method('quote')->willReturnCallback(function (string $value): string {
            return '"' . $value . '"';
        });
        $result = $this->subject->like('aField', "'aValue%'");
        self::assertSame("aField LIKE 'aValue%' ESCAPE \"\\\"", $result);
    }

    #[Test]
    public function notLikeQuotesLiteral(): void
    {
        $databasePlatform = $this->createMock(MockMySQLPlatform::class);
        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);
        $this->connectionMock->expects($this->atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $this->connectionMock->method('quote')->willReturnCallback(function (string $value): string {
            return '"' . $value . '"';
        });
        $result = $this->subject->notLike('aField', "'aValue%'");
        self::assertSame("aField NOT LIKE 'aValue%' ESCAPE \"\\\"", $result);
    }

    #[Test]
    public function inWithStringQuotesIdentifier(): void
    {
        $this->connectionMock->expects($this->atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $result = $this->subject->in('aField', '1,2,3');

        self::assertSame('aField IN (1,2,3)', $result);
    }

    #[Test]
    public function inWithArrayQuotesIdentifier(): void
    {
        $this->connectionMock->expects($this->atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $result = $this->subject->in('aField', [1, 2, 3]);

        self::assertSame('aField IN (1, 2, 3)', $result);
    }

    #[Test]
    public function inThrowsExceptionWithEmptyArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1701857902);
        $this->subject->in('aField', []);
    }

    #[Test]
    public function inThrowsExceptionWithEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1701857903);
        $this->subject->in('aField', '');
    }

    #[Test]
    public function notInWithStringQuotesIdentifier(): void
    {
        $this->connectionMock->expects($this->atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $result = $this->subject->notIn('aField', '1,2,3');

        self::assertSame('aField NOT IN (1,2,3)', $result);
    }

    #[Test]
    public function notInWithArrayQuotesIdentifier(): void
    {
        $this->connectionMock->expects($this->atLeastOnce())->method('quoteIdentifier')->with('aField')->willReturnArgument(0);
        $result = $this->subject->notIn('aField', [1, 2, 3]);

        self::assertSame('aField NOT IN (1, 2, 3)', $result);
    }

    #[Test]
    public function notInThrowsExceptionWithEmptyArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1701857904);
        $this->subject->notIn('aField', []);
    }

    #[Test]
    public function notInThrowsExceptionWithEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1701857905);
        $this->subject->notIn('aField', '');
    }

    #[Test]
    public function inSetThrowsExceptionWithEmptyValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1459696089);
        $this->subject->inSet('aField', '');
    }

    #[Test]
    public function inSetThrowsExceptionWithInvalidValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1459696090);
        $this->subject->inSet('aField', 'an,Invalid,Value');
    }

    #[Test]
    public function inSetForMySQL(): void
    {
        $databasePlatform = $this->createMock(MockMySQLPlatform::class);
        $this->connectionMock->method('quoteIdentifier')->willReturnCallback(static function (string $identifier): string {
            return '`' . $identifier . '`';
        });

        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        $result = $this->subject->inSet('aField', "'1'");

        self::assertSame('FIND_IN_SET(\'1\', `aField`)', $result);
    }

    #[Test]
    public function inSetForPostgreSQL(): void
    {
        $databasePlatform = $this->createMock(MockPostgreSQLPlatform::class);
        $series = [
            ['1', "'1'"],
            [',', "','"],
        ];
        $this->connectionMock->expects($this->exactly(2))->method('quote')
            ->willReturnCallback(function (string $value) use (&$series): string {
                $arguments = array_shift($series);
                self::assertSame($arguments[0], $value);
                return $arguments[1];
            });
        $this->connectionMock->method('quoteIdentifier')->willReturnCallback(static function (string $identifier): string {
            return '"' . $identifier . '"';
        });

        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        $result = $this->subject->inSet('aField', "'1'");

        self::assertSame('\'1\' = ANY(string_to_array("aField"::text, \',\'))', $result);
    }

    #[Test]
    public function inSetForPostgreSQLWithColumn(): void
    {
        $databasePlatform = $this->createMock(MockPostgreSQLPlatform::class);
        $this->connectionMock->expects($this->atLeastOnce())->method('quote')->with(',')->willReturn("','");
        $this->connectionMock->method('quoteIdentifier')->willReturnCallback(static function (string $identifier): string {
            return '"' . $identifier . '"';
        });

        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        $result = $this->subject->inSet('aField', '"testtable"."uid"', true);

        self::assertSame('"testtable"."uid"::text = ANY(string_to_array("aField"::text, \',\'))', $result);
    }

    #[Test]
    public function inSetForSQLite(): void
    {
        $databasePlatform = $this->createMock(MockSQLitePlatform::class);
        $series = [
            [',', "','"],
            [',', "','"],
            [',1,', "'%,1,%'"],
        ];
        $this->connectionMock->expects($this->exactly(3))->method('quote')
            ->willReturnCallback(function (string $value) use (&$series): string {
                $arguments = array_shift($series);
                self::assertSame($arguments[0], $value);
                return $arguments[1];
            });

        $this->connectionMock->method('quoteIdentifier')->willReturnCallback(static function (string $identifier): string {
            return '"' . $identifier . '"';
        });
        $databasePlatform->method('quoteStringLiteral')->willReturnCallback(static function (string $str): string {
            return "'" . str_replace("'", "''", $str) . "'";
        });

        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        $result = $this->subject->inSet('aField', "'1'");

        self::assertSame('instr(\',\'||"aField"||\',\', \'%,1,%\')', $result);
    }

    #[Test]
    public function inSetForSQLiteWithQuoteCharactersInValue(): void
    {
        $databasePlatform = $this->createMock(MockSQLitePlatform::class);
        $series = [
            [',', "','"],
            [',', "','"],
            [',\'Some\'Value,', "',''Some''Value,'"],
        ];
        $this->connectionMock->expects($this->exactly(3))->method('quote')
            ->willReturnCallback(function (string $value) use (&$series): string {
                $arguments = array_shift($series);
                self::assertSame($arguments[0], $value);
                return $arguments[1];
            });
        $this->connectionMock->method('quoteIdentifier')->willReturnCallback(static function (string $identifier): string {
            return '"' . $identifier . '"';
        });
        $databasePlatform->method('quoteStringLiteral')->willReturnCallback(static function (string $str): string {
            return "'" . str_replace("'", "''", $str) . "'";
        });

        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        $result = $this->subject->inSet('aField', "'''Some''Value'");

        self::assertSame('instr(\',\'||"aField"||\',\', \',\'\'Some\'\'Value,\')', $result);
    }

    #[Test]
    public function inSetForSQLiteThrowsExceptionOnPositionalPlaceholder(): void
    {
        $databasePlatform = $this->createMock(MockSQLitePlatform::class);
        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1476029421);

        $this->subject->inSet('aField', '?');
    }

    #[Test]
    public function inSetForSQLiteThrowsExceptionOnNamedPlaceholder(): void
    {
        $databasePlatform = $this->createMock(MockSQLitePlatform::class);
        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1476029421);

        $this->subject->inSet('aField', ':dcValue1');
    }

    #[Test]
    public function notInSetThrowsExceptionWithEmptyValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1627573099);
        $this->subject->notInSet('aField', '');
    }

    #[Test]
    public function notInSetThrowsExceptionWithInvalidValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1627573100);
        $this->subject->notInSet('aField', 'an,Invalid,Value');
    }

    #[Test]
    public function notInSetForMySQL(): void
    {
        $databasePlatform = $this->createMock(MockMySQLPlatform::class);

        $this->connectionMock->method('quoteIdentifier')->willReturnCallback(static function (string $identifier): string {
            return '`' . $identifier . '`';
        });

        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        $result = $this->subject->notInSet('aField', "'1'");

        self::assertSame('NOT FIND_IN_SET(\'1\', `aField`)', $result);
    }

    #[Test]
    public function notInSetForPostgreSQL(): void
    {
        $databasePlatform = $this->createMock(MockPostgreSQLPlatform::class);
        $series = [
            ['1', "'1'"],
            [',', "','"],
        ];
        $this->connectionMock->expects($this->exactly(2))->method('quote')
            ->willReturnCallback(function (string $value) use (&$series): string {
                $arguments = array_shift($series);
                self::assertSame($arguments[0], $value);
                return $arguments[1];
            });
        $this->connectionMock->method('quoteIdentifier')->willReturnCallback(static function (string $identifier): string {
            return '"' . $identifier . '"';
        });

        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        $result = $this->subject->notInSet('aField', "'1'");

        self::assertSame('\'1\' <> ALL(string_to_array("aField"::text, \',\'))', $result);
    }

    #[Test]
    public function notInSetForPostgreSQLWithColumn(): void
    {
        $databasePlatform = $this->createMock(MockPostgreSQLPlatform::class);

        $this->connectionMock->expects($this->atLeastOnce())->method('quote')->with(',')->willReturn("','");
        $this->connectionMock->method('quoteIdentifier')->willReturnCallback(static function (string $identifier): string {
            return '"' . $identifier . '"';
        });

        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        $result = $this->subject->notInSet('aField', '"testtable"."uid"', true);

        self::assertSame('"testtable"."uid"::text <> ALL(string_to_array("aField"::text, \',\'))', $result);
    }

    #[Test]
    public function notInSetForSQLite(): void
    {
        $databasePlatform = $this->createMock(MockSQLitePlatform::class);
        $series = [
            [',', "','"],
            [',', "','"],
            [',1,', "'%,1,%'"],
        ];
        $this->connectionMock->expects($this->exactly(3))->method('quote')
            ->willReturnCallback(function (string $value) use (&$series): string {
                $arguments = array_shift($series);
                self::assertSame($arguments[0], $value);
                return $arguments[1];
            });
        $this->connectionMock->method('quoteIdentifier')->willReturnCallback(static function (string $identifier): string {
            return '"' . $identifier . '"';
        });

        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        $result = $this->subject->notInSet('aField', "'1'");

        self::assertSame('instr(\',\'||"aField"||\',\', \'%,1,%\') = 0', $result);
    }

    #[Test]
    public function notInSetForSQLiteWithQuoteCharactersInValue(): void
    {
        $databasePlatform = $this->createMock(MockSQLitePlatform::class);
        $series = [
            [',', "','"],
            [',', "','"],
            [',\'Some\'Value,', "',''Some''Value,'"],
        ];
        $this->connectionMock->expects($this->exactly(3))->method('quote')
            ->willReturnCallback(function (string $value) use (&$series): string {
                $arguments = array_shift($series);
                self::assertSame($arguments[0], $value);
                return $arguments[1];
            });
        $this->connectionMock->method('quoteIdentifier')->willReturnCallback(static function (string $identifier): string {
            return '"' . $identifier . '"';
        });

        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        $result = $this->subject->notInSet('aField', "'''Some''Value'");

        self::assertSame('instr(\',\'||"aField"||\',\', \',\'\'Some\'\'Value,\') = 0', $result);
    }

    #[Test]
    public function notInSetForSQLiteThrowsExceptionOnPositionalPlaceholder(): void
    {
        $databasePlatform = $this->createMock(MockSQLitePlatform::class);
        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1627573103);

        $this->subject->notInSet('aField', '?');
    }

    #[Test]
    public function notInSetForSQLiteThrowsExceptionOnNamedPlaceholder(): void
    {
        $databasePlatform = $this->createMock(MockSQLitePlatform::class);
        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1476029421);

        $this->subject->inSet('aField', ':dcValue1');
    }

    #[Test]
    public function defaultBitwiseAnd(): void
    {
        $databasePlatform = $this->createMock(MockMySQLPlatform::class);

        $this->connectionMock->method('quoteIdentifier')->willReturnCallback(static function (string $identifier): string {
            return '"' . $identifier . '"';
        });

        $this->connectionMock->method('getDatabasePlatform')->willReturn($databasePlatform);

        self::assertSame('"aField" & 1', $this->subject->bitAnd('aField', 1));
    }

    #[Test]
    public function maxQuotesIdentifier(): void
    {
        $this->connectionMock->method('quoteIdentifier')->willReturnCallback(static function (string $identifier): string {
            return (new MockPlatform())->quoteIdentifier($identifier);
        });

        self::assertSame('MAX("tableName"."fieldName")', $this->subject->max('tableName.fieldName'));
        self::assertSame(
            'MAX("tableName"."fieldName") AS "anAlias"',
            $this->subject->max('tableName.fieldName', 'anAlias')
        );
    }

    #[Test]
    public function minQuotesIdentifier(): void
    {
        $this->connectionMock->method('quoteIdentifier')->willReturnCallback(static function (string $identifier): string {
            return (new MockPlatform())->quoteIdentifier($identifier);
        });

        self::assertSame('MIN("tableName"."fieldName")', $this->subject->min('tableName.fieldName'));
        self::assertSame(
            'MIN("tableName"."fieldName") AS "anAlias"',
            $this->subject->min('tableName.fieldName', 'anAlias')
        );
    }

    #[Test]
    public function sumQuotesIdentifier(): void
    {
        $this->connectionMock->method('quoteIdentifier')->willReturnCallback(static function (string $identifier): string {
            return (new MockPlatform())->quoteIdentifier($identifier);
        });

        self::assertSame('SUM("tableName"."fieldName")', $this->subject->sum('tableName.fieldName'));
        self::assertSame(
            'SUM("tableName"."fieldName") AS "anAlias"',
            $this->subject->sum('tableName.fieldName', 'anAlias')
        );
    }

    #[Test]
    public function avgQuotesIdentifier(): void
    {
        $this->connectionMock->method('quoteIdentifier')->willReturnCallback(static function (string $identifier): string {
            return (new MockPlatform())->quoteIdentifier($identifier);
        });

        self::assertSame('AVG("tableName"."fieldName")', $this->subject->avg('tableName.fieldName'));
        self::assertSame(
            'AVG("tableName"."fieldName") AS "anAlias"',
            $this->subject->avg('tableName.fieldName', 'anAlias')
        );
    }

    #[Test]
    public function countQuotesIdentifier(): void
    {
        $this->connectionMock->method('quoteIdentifier')->willReturnCallback(static function (string $identifier): string {
            return (new MockPlatform())->quoteIdentifier($identifier);
        });

        self::assertSame('COUNT("tableName"."fieldName")', $this->subject->count('tableName.fieldName'));
        self::assertSame(
            'COUNT("tableName"."fieldName") AS "anAlias"',
            $this->subject->count('tableName.fieldName', 'anAlias')
        );
    }

    #[Test]
    public function lengthQuotesIdentifier(): void
    {
        $this->connectionMock->method('quoteIdentifier')->willReturnCallback(static function (string $identifier): string {
            return (new MockPlatform())->quoteIdentifier($identifier);
        });

        self::assertSame('LENGTH("tableName"."fieldName")', $this->subject->length('tableName.fieldName'));
        self::assertSame(
            'LENGTH("tableName"."fieldName") AS "anAlias"',
            $this->subject->length('tableName.fieldName', 'anAlias')
        );
    }

    #[Test]
    public function trimQuotesIdentifierWithDefaultValues(): void
    {
        $platform = new MockPlatform();
        $this->connectionMock->expects($this->atLeastOnce())->method('getDatabasePlatform')->willReturn($platform);
        $this->connectionMock->expects($this->atLeastOnce())->method('quoteIdentifier')
            ->willReturnCallback(
                static function (string $identifier) use ($platform): string {
                    return $platform->quoteIdentifier($identifier);
                }
            );

        self::assertSame(
            'TRIM("tableName"."fieldName")',
            $this->subject->trim('tableName.fieldName')
        );
    }

    public static function trimQuotesIdentifierDataProvider(): array
    {
        return [
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

    #[DataProvider('trimQuotesIdentifierDataProvider')]
    #[Test]
    public function trimQuotesIdentifier(TrimMode $position, string $char, string $expected): void
    {
        $platform = new MockPlatform();
        $this->connectionMock->expects($this->atLeastOnce())->method('getDatabasePlatform')->willReturn($platform);
        $this->connectionMock->expects($this->atLeastOnce())->method('quoteIdentifier')
            ->willReturnCallback(
                static function (string $identifier) use ($platform): string {
                    return $platform->quoteIdentifier($identifier);
                }
            );
        $this->connectionMock->expects($this->atLeastOnce())->method('quote')->willReturnCallback(
            static function (string $identifier): string {
                return '"' . $identifier . '"';
            }
        );

        self::assertSame(
            $expected,
            $this->subject->trim('tableName.fieldName', $position, $char)
        );
    }

    #[Test]
    public function literalQuotesValue(): void
    {
        $this->connectionMock->expects($this->atLeastOnce())->method('quote')->with('aField')
            ->willReturn('"aField"');
        $result = $this->subject->literal('aField');

        self::assertSame('"aField"', $result);
    }

    public static function castTextDataProvider(): array
    {
        return [
            'Test cast for MySQLPlatform' => [
                'platform' => new DoctrineMySQLPlatform(),
                'expectation' => '(CAST((1 * 10) AS CHAR(16383)))',
            ],
            'Test cast for MariaDBPlatform' => [
                'platform' => new DoctrineMariaDBPlatform(),
                'expectation' => '(CAST((1 * 10) AS VARCHAR(16383)))',
            ],
            'Test cast for PostgreSqlPlatform' => [
                'platform' => new DoctrinePostgreSQLPlatform(),
                'expectation' => '((1 * 10)::text)',
            ],
            'Test cast for SqlitePlatform' => [
                'platform' => new DoctrineSQLitePlatform(),
                'expectation' => '(CAST((1 * 10) AS TEXT))',
            ],
        ];
    }

    #[DataProvider('castTextDataProvider')]
    #[Test]
    public function castText(AbstractPlatform $platform, string $expectation): void
    {
        $this->connectionMock->method('getDatabasePlatform')->willReturn($platform);
        $result = (new ExpressionBuilder($this->connectionMock))->castText('1 * 10');
        self::assertSame($expectation, $result);
    }

    #[DataProvider('castTextDataProvider')]
    #[Test]
    public function castTextAsVirtualIdentifier(AbstractPlatform $platform, string $expectation): void
    {
        $this->connectionMock->method('getDatabasePlatform')->willReturn($platform);
        $this->connectionMock->method('quoteIdentifier')->willReturnArgument(0);
        $result = (new ExpressionBuilder($this->connectionMock))->castText('1 * 10', 'virtual_identifier');
        self::assertSame($expectation . ' AS virtual_identifier', $result);
    }

    #[Test]
    public function castTextThrowsRuntimeExceptionForUnsupportedPlatform(): void
    {
        $this->connectionMock->method('getDatabasePlatform')->willReturn(new MockPlatform());

        self::expectException(\RuntimeException::class);
        self::expectExceptionCode(1722105672);

        (new ExpressionBuilder($this->connectionMock))->castText('1 * 10', 'virtual_identifier');
    }
}
