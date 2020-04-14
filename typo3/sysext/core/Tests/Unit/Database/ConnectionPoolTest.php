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

namespace TYPO3\CMS\Core\Tests\Unit\Database;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ConnectionPoolTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getConnectionNamesReturnsConfiguredConnectionNames()
    {
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'] = [
            'Default' => [
                'aConfigDetail' => '',
            ],
            'klaus' => [
                'anotherConfigDetail' => '',
            ],
        ];
        self::assertSame(['Default', 'klaus'], (new ConnectionPool())->getConnectionNames());
    }
}
