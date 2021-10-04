<?php

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

declare(strict_types=1);

namespace TYPO3\CMS\Core\Authentication\Mfa\Provider;

use Base32\Base32;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Time-based one-time password (TOTP) implementation according to rfc6238
 *
 * @internal should only be used by the TYPO3 Core
 */
class Totp
{
    private const ALLOWED_ALGOS = ['sha1', 'sha256', 'sha512'];
    private const MIN_LENGTH = 6;
    private const MAX_LENGTH = 8;

    protected string $secret;
    protected string $algo;
    protected int $length;
    protected int $step;
    protected int $epoch;

    public function __construct(
        string $secret,
        string $algo = 'sha1',
        int $length = 6,
        int $step = 30,
        int $epoch = 0
    ) {
        $this->secret = $secret;
        $this->step = $step;
        $this->epoch = $epoch;

        if (!in_array($algo, self::ALLOWED_ALGOS, true)) {
            throw new \InvalidArgumentException(
                $algo . ' is not allowed. Allowed algos are: ' . implode(',', self::ALLOWED_ALGOS),
                1611748791
            );
        }
        $this->algo = $algo;

        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                $length . ' is not allowed as TOTP length. Must be between ' . self::MIN_LENGTH . ' and ' . self::MAX_LENGTH,
                1611748792
            );
        }
        $this->length = $length;
    }

    /**
     * Generate a time-based one-time password for the given counter according to rfc4226
     *
     * @param int $counter A timestamp (counter) according to rfc6238
     * @return string The generated TOTP
     */
    public function generateTotp(int $counter): string
    {
        // Generate a 8-byte counter value (C) from the given counter input
        $binary = [];
        while ($counter !== 0) {
            $binary[] = pack('C*', $counter);
            $counter >>= 8;
        }
        // Implode and fill with NULL values
        $binary = str_pad(implode(array_reverse($binary)), 8, "\000", STR_PAD_LEFT);
        // Create a 20-byte hash string (HS) with given algo and decoded shared secret (K)
        $hash = hash_hmac($this->algo, $binary, $this->getDecodedSecret());
        // Convert hash into hex and generate an array with the decimal values of the hash
        $hmac = [];
        foreach (str_split($hash, 2) as $hex) {
            $hmac[] = hexdec($hex);
        }
        // Generate a 4-byte string with dynamic truncation (DT)
        $offset = $hmac[\count($hmac) - 1] & 0xf;
        $bits = ((($hmac[$offset + 0] & 0x7f) << 24) | (($hmac[$offset + 1] & 0xff) << 16) | (($hmac[$offset + 2] & 0xff) << 8) | ($hmac[$offset + 3] & 0xff));
        // Compute the TOTP value by reducing the bits modulo 10^Digits and filling it with zeros '0'
        return str_pad((string)($bits % (10 ** $this->length)), $this->length, '0', STR_PAD_LEFT);
    }

    /**
     * Verify the given time-based one-time password
     *
     * @param string $totp The time-based one-time password to be verified
     * @param int|null $gracePeriod The grace period for the TOTP +- (mainly to circumvent transmission delays)
     * @return bool
     */
    public function verifyTotp(string $totp, int $gracePeriod = null): bool
    {
        $counter = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp');

        // If no grace period is given, only check once
        if ($gracePeriod === null) {
            return $this->compare($totp, $this->getTimeCounter($counter));
        }

        // Check the token within the given grace period till it can be verified or the grace period is exhausted
        for ($i = 0; $i < $gracePeriod; ++$i) {
            $next = $i * $this->step + $counter;
            $prev = $counter - $i * $this->step;
            if ($this->compare($totp, $this->getTimeCounter($next))
                || $this->compare($totp, $this->getTimeCounter($prev))
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate and return the otpauth URL for TOTP
     *
     * @param string $issuer
     * @param string $account
     * @param array $additionalParameters
     * @return string
     */
    public function getTotpAuthUrl(string $issuer, string $account = '', array $additionalParameters = []): string
    {
        $parameters = [
            'secret' => $this->secret,
            'issuer' => htmlspecialchars($issuer),
        ];

        // Common OTP applications expect the following parameters:
        // - algo: sha1
        // - period: 30 (in seconds)
        // - digits 6
        // - epoch: 0
        // Only if we differ from these assumption, the exact values must be provided.
        if ($this->algo !== 'sha1') {
            $parameters['algorithm'] = $this->algo;
        }
        if ($this->step !== 30) {
            $parameters['period'] = $this->step;
        }
        if ($this->length !== 6) {
            $parameters['digits'] = $this->length;
        }
        if ($this->epoch !== 0) {
            $parameters['epoch'] = $this->epoch;
        }

        // Generate the otpauth URL by providing information like issuer and account
        return sprintf(
            'otpauth://totp/%s?%s',
            rawurlencode($issuer . ($account !== '' ? ':' . $account : '')),
            http_build_query(array_merge($parameters, $additionalParameters), '', '&', PHP_QUERY_RFC3986)
        );
    }

    /**
     * Compare given time-based one-time password with a time-based one-time
     * password generated from the known $counter (the moving factor).
     *
     * @param string $totp The time-based one-time password to verify
     * @param int $counter The counter value, the moving factor
     * @return bool
     */
    protected function compare(string $totp, int $counter): bool
    {
        return hash_equals($this->generateTotp($counter), $totp);
    }

    /**
     * Generate the counter value (moving factor) from the given timestamp
     *
     * @param int $timestamp
     * @return int
     */
    protected function getTimeCounter(int $timestamp): int
    {
        return (int)floor(($timestamp - $this->epoch) / $this->step);
    }

    /**
     * Generate the shared secret (K) by using a random and applying
     * additional authentication factors like username or email address.
     *
     * @param array $additionalAuthFactors
     * @return string
     */
    public static function generateEncodedSecret(array $additionalAuthFactors = []): string
    {
        $secret = '';
        $payload = implode($additionalAuthFactors);
        // Prevent secrets with a trailing pad character since this will eventually break the QR-code feature
        while ($secret === '' || str_contains($secret, '=')) {
            // RFC 4226 (https://tools.ietf.org/html/rfc4226#section-4) suggests 160 bit TOTP secret keys
            // HMAC-SHA1 based on static factors and a 160 bit HMAC-key lead again to 160 bits (20 bytes)
            // base64-encoding (factor 1.6) 20 bytes lead to 32 uppercase characters
            $secret = Base32::encode(hash_hmac('sha1', $payload, random_bytes(20), true));
        }
        return $secret;
    }

    protected function getDecodedSecret(): string
    {
        return Base32::decode($this->secret);
    }
}
