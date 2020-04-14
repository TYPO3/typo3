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

use Prophecy\Argument;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AbstractRestrictionTestCase extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder
     */
    protected $expressionBuilder;

    /**
     * Create a new database connection mock object for every test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        /** @var Connection|\Prophecy\Prophecy\ObjectProphecy $connection */
        $connection = $this->prophesize(Connection::class);
        $connection->quoteIdentifier(Argument::cetera())->will(function ($args) {
            return '"' . implode('"."', explode('.', $args[0])) . '"';
        });
        $connection->quote(Argument::cetera())->will(function ($args) {
            return '\'' . $args[0] . '\'';
        });
        $connection->getDatabasePlatform()->willReturn(new MockPlatform());

        $this->expressionBuilder = GeneralUtility::makeInstance(ExpressionBuilder::class, $connection->reveal());
    }
}
