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

namespace TYPO3\CMS\Core\Authentication;

use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Handles the locking of sessions to IP addresses.
 */
class IpLocker
{
    const DISABLED_LOCK_VALUE = '[DISABLED]';

    /**
     * If set to 4, the session will be locked to the user's IP address (all four numbers).
     * Reducing this to 1-3 means that only the given number of parts of the IP address is used.
     *
     * @var int
     */
    protected $lockIPv4PartCount = 4;

    /**
     * Same setting as lockIP but for IPv6 addresses.
     *
     * @var int
     */
    protected $lockIPv6PartCount = 8;

    public function __construct(int $lockIPv4PartCount, int $lockIPv6PartCount)
    {
        $this->lockIPv4PartCount = $lockIPv4PartCount;
        $this->lockIPv6PartCount = $lockIPv6PartCount;
    }

    public function getSessionIpLock(string $ipAddress): string
    {
        if ($this->lockIPv4PartCount === 0 && $this->lockIPv6PartCount === 0) {
            return static::DISABLED_LOCK_VALUE;
        }

        if ($this->isIpv6Address($ipAddress)) {
            return $this->getIpLockPartForIpv6Address($ipAddress);
        }
        return $this->getIpLockPartForIpv4Address($ipAddress);
    }

    public function validateRemoteAddressAgainstSessionIpLock(string $ipAddress, string $sessionIpLock): bool
    {
        if ($sessionIpLock === static::DISABLED_LOCK_VALUE) {
            return true;
        }

        $ipToCompare = $this->isIpv6Address($ipAddress)
            ? $this->getIpLockPartForIpv6Address($ipAddress)
            : $this->getIpLockPartForIpv4Address($ipAddress);
        return $ipToCompare === $sessionIpLock;
    }

    protected function getIpLockPart(string $ipAddress, int $numberOfParts, int $maxParts, string $delimiter): string
    {
        if ($numberOfParts >= $maxParts) {
            return $ipAddress;
        }

        $numberOfParts = MathUtility::forceIntegerInRange($numberOfParts, 1, $maxParts);
        $ipParts = explode($delimiter, $ipAddress);
        if ($ipParts === false) {
            return $ipAddress;
        }
        for ($a = $maxParts; $a > $numberOfParts; $a--) {
            $ipPartValue = $delimiter === '.' ? '0' : str_pad('', strlen($ipParts[$a - 1]), '0');
            $ipParts[$a - 1] = $ipPartValue;
        }

        return implode($delimiter, $ipParts);
    }

    protected function getIpLockPartForIpv4Address(string $ipAddress): string
    {
        if ($this->lockIPv4PartCount === 0) {
            return static::DISABLED_LOCK_VALUE;
        }

        return $this->getIpLockPart($ipAddress, $this->lockIPv4PartCount, 4, '.');
    }

    protected function getIpLockPartForIpv6Address(string $ipAddress): string
    {
        if ($this->lockIPv6PartCount === 0) {
            return static::DISABLED_LOCK_VALUE;
        }

        // inet_pton also takes care of IPv4-mapped addresses (see https://en.wikipedia.org/wiki/IPv6_address#Representation)
        $unpacked = unpack('H*hex', (string)inet_pton($ipAddress)) ?: [];
        $expandedAddress = rtrim(chunk_split($unpacked['hex'] ?? '', 4, ':'), ':');
        return $this->getIpLockPart($expandedAddress, $this->lockIPv6PartCount, 8, ':');
    }

    protected function isIpv6Address(string $ipAddress): bool
    {
        return str_contains($ipAddress, ':');
    }
}
