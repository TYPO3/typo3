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

namespace TYPO3\CMS\Core\Tests\Functional\Database\Query\Restriction;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform\MockMySQLPlatform;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class AbstractRestrictionTestCase extends FunctionalTestCase
{
    protected ExpressionBuilder $expressionBuilder;

    /**
     * Create a new database connection mock object for every test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $connection = $this->createMock(Connection::class);
        $connection->method('quoteIdentifier')->willReturnCallback(static function (string $identifier): string {
            return '"' . implode('"."', explode('.', $identifier)) . '"';
        });
        $connection->method('quote')->willReturnCallback(static function (string $value): string {
            return '\'' . $value . '\'';
        });
        $connection->method('getDatabasePlatform')->willReturn(new MockMySQLPlatform());

        $this->expressionBuilder = new ExpressionBuilder($connection);
    }
}
