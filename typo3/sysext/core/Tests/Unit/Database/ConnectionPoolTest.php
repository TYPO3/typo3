<?php
declare(strict_types=1);
namespace TYPO3\CMS\Core\Tests\Unit\Database;

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

use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Test case
 */
class ConnectionPoolTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
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
        $this->assertSame(['Default', 'klaus'], (new ConnectionPool())->getConnectionNames());
    }
}
