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

namespace TYPO3\CMS\Core\Tests\Unit\Utility;

use TYPO3\CMS\Core\Utility\IpAnonymizationUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for class \TYPO3\CMS\Core\Utility\IpAnonymizationUtility
 */
class IpAnonymizationUtilityTest extends UnitTestCase
{

    /**
     * Data provider for anonymizeIpReturnsCorrectValue
     *
     * @return array
     */
    public function anonymizeIpReturnsCorrectValueDataProvider(): array
    {
        return [
            'empty address' => ['', 1, ''],
            'IPv4 address with mask 0' => ['192.158.130.10', 0, '192.158.130.10'],
            'IPv4 address with mask 1' => ['192.158.130.10', 1, '192.158.130.0'],
            'IPv4 address with mask 2' => ['192.158.130.10', 2, '192.158.0.0'],
            'IPv4 address with fallback' => ['192.158.130.10', null, '192.158.130.0'],
            'IPv6 address with mask 0' => ['0064:ff9b:0000:0000:0000:0000:18.52.86.120', 0, '0064:ff9b:0000:0000:0000:0000:18.52.86.120'],
            'IPv6 address with mask 1' => ['2002:6dcd:8c74:6501:fb2:61c:ac98:6bea', 1, '2002:6dcd:8c74:6501::'],
            'IPv6 address with mask 2' => ['2002:6dcd:8c74:6501:fb2:61c:ac98:6bea', 2, '2002:6dcd:8c74::'],
            'IPv6 address with fallback' => ['2002:6dcd:8c74:6501:fb2:61c:ac98:6bea', null, '2002:6dcd:8c74:6501::'],
            'IPv4-Embedded IPv6 Address' => ['::ffff:18.52.86.120', 1, '::'],
            'anonymized IPv4 address' => ['192.158.0.0', 1, '192.158.0.0'],
            'invalid IPv4 address given' => ['127.0.01', 1, ''],
            'invalid IPv6 address given' => ['ffff18.52.86.120', 1, ''],
        ];
    }

    /**
     * @test
     * @dataProvider anonymizeIpReturnsCorrectValueDataProvider
     * @param string $address
     * @param int|null $mask
     * @param string $expected
     */
    public function anonymizeIpReturnsCorrectValue(string $address, int $mask = null, string $expected)
    {
        // set the default if $mask is null
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['ipAnonymization'] = 1;
        self::assertEquals($expected, IpAnonymizationUtility::anonymizeIp($address, $mask));
    }

    /**
     * @test
     */
    public function wrongMaskForAnonymizeIpThrowsException()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1519739203);

        IpAnonymizationUtility::anonymizeIp('', 3);
    }
}
