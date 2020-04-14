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

namespace TYPO3\CMS\Core\Tests\Unit\Authentication;

use TYPO3\CMS\Core\Authentication\IpLocker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for class \TYPO3\CMS\Core\Authentication\IpLocker
 */
class IpLockerTest extends UnitTestCase
{
    /**
      * @return array
      */
    public function getSessionIpLockDataProvider(): array
    {
        return [
            'basic IPv4-locks, part-count 0' => [
                '192.168.0.1',
                0,
                8,
                '[DISABLED]',
            ],
            'basic IPv4-locks, part-count 1' => [
                '192.168.0.1',
                1,
                8,
                '192.0.0.0',
            ],
            'basic IPv4-locks, part-count 2' => [
                '192.168.0.1',
                2,
                8,
                '192.168.0.0',
            ],
            'basic IPv4-locks, part-count 3' => [
                '192.168.0.1',
                3,
                8,
                '192.168.0.0',
            ],
            'basic IPv4-locks, part-count 4' => [
                '192.168.0.1',
                4,
                8,
                '192.168.0.1',
            ],
            'basic IPv6-locks, part-count 0' => [
                '2001:0db8:85a3:08d3:1319:8a2e:0370:7344',
                4,
                0,
                '[DISABLED]',
            ],
            'basic IPv6-locks, part-count 1' => [
                '2001:0db8:85a3:08d3:1319:8a2e:0370:7344',
                4,
                1,
                '2001:0000:0000:0000:0000:0000:0000:0000',
            ],
            'basic IPv6-locks, part-count 2' => [
                '2001:0db8:85a3:08d3:1319:8a2e:0370:7344',
                4,
                2,
                '2001:0db8:0000:0000:0000:0000:0000:0000',
            ],
            'basic IPv6-locks, part-count 3' => [
                '2001:0db8:85a3:08d3:1319:8a2e:0370:7344',
                4,
                3,
                '2001:0db8:85a3:0000:0000:0000:0000:0000',
            ],
            'basic IPv6-locks, part-count 4' => [
                '2001:0db8:85a3:08d3:1319:8a2e:0370:7344',
                4,
                4,
                '2001:0db8:85a3:08d3:0000:0000:0000:0000',
            ],
            'basic IPv6-locks, part-count 5' => [
                '2001:0db8:85a3:08d3:1319:8a2e:0370:7344',
                4,
                5,
                '2001:0db8:85a3:08d3:1319:0000:0000:0000',
            ],
            'basic IPv6-locks, part-count 6' => [
                '2001:0db8:85a3:08d3:1319:8a2e:0370:7344',
                4,
                6,
                '2001:0db8:85a3:08d3:1319:8a2e:0000:0000',
            ],
            'basic IPv6-locks, part-count 7' => [
                '2001:0db8:85a3:08d3:1319:8a2e:0370:7344',
                4,
                7,
                '2001:0db8:85a3:08d3:1319:8a2e:0370:0000',
            ],
            'basic IPv6-locks, part-count 8' => [
                '2001:0db8:85a3:08d3:1319:8a2e:0370:7344',
                4,
                8,
                '2001:0db8:85a3:08d3:1319:8a2e:0370:7344',
            ],
            'compressed IPv6-lock, IP ::1, part-count 8' => [
                '::1',
                4,
                8,
                '0000:0000:0000:0000:0000:0000:0000:0001',
            ],
            'compressed IPv6-lock, IP 2001:db8:0:200::7, part-count 8' => [
                '2001:db8:0:200::7',
                4,
                8,
                '2001:0db8:0000:0200:0000:0000:0000:0007',
            ],
            'compressed IPv6-lock, IPv4-mapped IP ::ffff:127.0.0.1, part-count 8' => [
                '::ffff:127.0.0.1',
                4,
                8,
                '0000:0000:0000:0000:0000:ffff:7f00:0001',
            ],
        ];
    }

    /**
     * @param string $ipAddress
     * @param $lockIPv4PartCount
     * @param $lockIPv6PartCount
     * @param string $expectedLock
     * @test
     * @dataProvider getSessionIpLockDataProvider
     */
    public function getSessionIpLock($ipAddress, $lockIPv4PartCount, $lockIPv6PartCount, $expectedLock): void
    {
        $ipLocker = GeneralUtility::makeInstance(IpLocker::class, $lockIPv4PartCount, $lockIPv6PartCount);
        $lock = $ipLocker->getSessionIpLock($ipAddress);

        self::assertEquals($expectedLock, $lock);
    }
}
