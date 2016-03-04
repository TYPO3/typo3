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
    public function literalQuotesValue()
    {
        $this->connectionProphet->quote('aField', 'Doctrine\DBAL\Types\StringType')
            ->shouldBeCalled()
            ->willReturn('"aField"');
        $result = $this->subject->literal('aField', 'Doctrine\DBAL\Types\StringType');

        $this->assertSame('"aField"', $result);
    }
}
