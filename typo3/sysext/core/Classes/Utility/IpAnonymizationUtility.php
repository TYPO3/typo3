<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Utility;

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

/**
 * Anonymize a given IP
 *
 * Inspired by https://github.com/geertw/php-ip-anonymizer
 */
class IpAnonymizationUtility
{

    /**
     * IPv4 netmask used to anonymize IPv4 address.
     *
     * 1) Mask host
     * 2) Mask host and subnet
     *
     * @var array
     */
    const MASKV4 = [
        1 => '255.255.255.0',
        2 => '255.255.0.0'
    ];

    /**
     * IPv6 netmask used to anonymize IPv6 address.
     *
     * 1) Mask Interface ID
     * 2) Mask Interface ID and SLA ID
     *
     * @var array
     */
    const MASKV6 = [
        1 => 'ffff:ffff:ffff:ffff:0000:0000:0000:0000',
        2 => 'ffff:ffff:ffff:0000:0000:0000:0000:0000',
    ];

    /**
     * Anonymize given IP
     *
     * @param string $address IP address
     * @param int $mask Allowed values are 0 (masking disabled), 1 (mask host), 2 (mask host and subnet)
     * @return string
     * @throws \UnexpectedValueException
     */
    public static function anonymizeIp(string $address, int $mask = null): string
    {
        if ($mask === null) {
            $mask = (int)$GLOBALS['TYPO3_CONF_VARS']['SYS']['ipAnonymization'];
        }
        if ($mask < 0 || $mask > 2) {
            throw new \UnexpectedValueException(sprintf('The provided value "%d" is not an allowed value for the IP mask.', $mask), 1519739203);
        }
        if ($mask === 0) {
            return $address;
        }
        if (empty($address)) {
            return '';
        }

        $packedAddress = @inet_pton($address);
        if ($packedAddress === false) {
            return '';
        }
        $length = strlen($packedAddress);

        if ($length === 4) {
            $bitMask = self::MASKV4[$mask];
        } elseif ($length === 16) {
            $bitMask = self::MASKV6[$mask];
        } else {
            return '';
        }
        return inet_ntop($packedAddress & inet_pton($bitMask));
    }
}
