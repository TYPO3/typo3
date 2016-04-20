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

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform;
use TYPO3\CMS\Core\Tests\UnitTestCase;

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
     *
     * @return void
     */
    protected function setUp()
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

        $this->assertInstanceOf(CompositeExpression::class, $result);
        $this->assertSame(CompositeExpression::TYPE_AND, $result->getType());
    }

    /**
     * @test
     */
    public function orXReturnType()
    {
        $result = $this->subject->orX('"uid" = 1', '"uid" = 7');

        $this->assertInstanceOf(CompositeExpression::class, $result);
        $this->assertSame(CompositeExpression::TYPE_OR, $result->getType());
    }

    /**
     * @test
     */
    public function eqQuotesIdentifier()
    {
        $result = $this->subject->eq('aField', 1);

        $this->connectionProphet->quoteIdentifier('aField')->shouldHaveBeenCalled();
        $this->assertSame('aField = 1', $result);
    }

    /**
     * @test
     */
    public function neqQuotesIdentifier()
    {
        $result = $this->subject->neq('aField', 1);

        $this->connectionProphet->quoteIdentifier('aField')->shouldHaveBeenCalled();
        $this->assertSame('aField <> 1', $result);
    }

    /**
     * @test
     */
    public function ltQuotesIdentifier()
    {
        $result = $this->subject->lt('aField', 1);

        $this->connectionProphet->quoteIdentifier('aField')->shouldHaveBeenCalled();
        $this->assertSame('aField < 1', $result);
    }

    /**
     * @test
     */
    public function lteQuotesIdentifier()
    {
        $result = $this->subject->lte('aField', 1);

        $this->connectionProphet->quoteIdentifier('aField')->shouldHaveBeenCalled();
        $this->assertSame('aField <= 1', $result);
    }

    /**
     * @test
     */
    public function gtQuotesIdentifier()
    {
        $result = $this->subject->gt('aField', 1);

        $this->connectionProphet->quoteIdentifier('aField')->shouldHaveBeenCalled();
        $this->assertSame('aField > 1', $result);
    }

    /**
     * @test
     */
    public function gteQuotesIdentifier()
    {
        $result = $this->subject->gte('aField', 1);

        $this->connectionProphet->quoteIdentifier('aField')->shouldHaveBeenCalled();
        $this->assertSame('aField >= 1', $result);
    }

    /**
     * @test
     */
    public function isNullQuotesIdentifier()
    {
        $result = $this->subject->isNull('aField');

        $this->connectionProphet->quoteIdentifier('aField')->shouldHaveBeenCalled();
        $this->assertSame('aField IS NULL', $result);
    }

    /**
     * @test
     */
    public function isNotNullQuotesIdentifier()
    {
        $result = $this->subject->isNotNull('aField');

        $this->connectionProphet->quoteIdentifier('aField')->shouldHaveBeenCalled();
        $this->assertSame('aField IS NOT NULL', $result);
    }

    /**
     * @test
     */
    public function likeQuotesIdentifier()
    {
        $result = $this->subject->like('aField', "'aValue%'");

        $this->connectionProphet->quoteIdentifier('aField')->shouldHaveBeenCalled();
        $this->assertSame("aField LIKE 'aValue%'", $result);
    }

    /**
     * @test
     */
    public function notLikeQuotesIdentifier()
    {
        $result = $this->subject->notLike('aField', "'aValue%'");

        $this->connectionProphet->quoteIdentifier('aField')->shouldHaveBeenCalled();
        $this->assertSame("aField NOT LIKE 'aValue%'", $result);
    }

    /**
     * @test
     */
    public function inWithStringQuotesIdentifier()
    {
        $result = $this->subject->in('aField', '1,2,3');

        $this->connectionProphet->quoteIdentifier('aField')->shouldHaveBeenCalled();
        $this->assertSame('aField IN (1,2,3)', $result);
    }

    /**
     * @test
     */
    public function inWithArrayQuotesIdentifier()
    {
        $result = $this->subject->in('aField', [1, 2, 3]);

        $this->connectionProphet->quoteIdentifier('aField')->shouldHaveBeenCalled();
        $this->assertSame('aField IN (1, 2, 3)', $result);
    }

    /**
     * @test
     */
    public function notInWithStringQuotesIdentifier()
    {
        $result = $this->subject->notIn('aField', '1,2,3');

        $this->connectionProphet->quoteIdentifier('aField')->shouldHaveBeenCalled();
        $this->assertSame('aField NOT IN (1,2,3)', $result);
    }

    /**
     * @test
     */
    public function notInWithArrayQuotesIdentifier()
    {
        $result = $this->subject->notIn('aField', [1, 2, 3]);

        $this->connectionProphet->quoteIdentifier('aField')->shouldHaveBeenCalled();
        $this->assertSame('aField NOT IN (1, 2, 3)', $result);
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

        $this->assertSame('FIND_IN_SET(\'1\', `aField`)', $result);
    }

    /**
     * @test
     */
    public function inSetForPostgreSQL()
    {
        $databasePlatform = $this->prophesize(MockPlatform::class);
        $databasePlatform->getName()->willReturn('postgresql');

        $this->connectionProphet->quote(',', Argument::cetera())->shouldBeCalled()->willReturn("','");
        $this->connectionProphet->quoteIdentifier(Argument::cetera())->will(function ($args) {
            return '"' . $args[0] . '"';
        });

        $this->connectionProphet->getDatabasePlatform()->willReturn($databasePlatform->reveal());

        $result = $this->subject->inSet('aField', "'1'");

        $this->assertSame('any(string_to_array("aField", \',\')) = \'1\'', $result);
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

        $this->assertSame('"aField" & 1', $this->subject->bitAnd('aField', 1));
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

        $this->assertSame('BITAND("aField", 1)', $this->subject->bitAnd('aField', 1));
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

        $this->assertSame('MAX("tableName"."fieldName")', $this->subject->max('tableName.fieldName'));
        $this->assertSame(
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

        $this->assertSame('MIN("tableName"."fieldName")', $this->subject->min('tableName.fieldName'));
        $this->assertSame(
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

        $this->assertSame('SUM("tableName"."fieldName")', $this->subject->sum('tableName.fieldName'));
        $this->assertSame(
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

        $this->assertSame('AVG("tableName"."fieldName")', $this->subject->avg('tableName.fieldName'));
        $this->assertSame(
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

        $this->assertSame('COUNT("tableName"."fieldName")', $this->subject->count('tableName.fieldName'));
        $this->assertSame(
            'COUNT("tableName"."fieldName") AS "anAlias"',
            $this->subject->count('tableName.fieldName', 'anAlias')
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

        $this->assertSame('"aField"', $result);
    }
}
