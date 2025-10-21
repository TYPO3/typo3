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

namespace TYPO3\CMS\Core\Authentication\Mfa\Provider;

use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Implementation for generation and validation of recovery codes
 *
 * @internal should only be used by the TYPO3 Core
 */
class RecoveryCodes
{
    private const MIN_LENGTH = 8;

    protected PasswordHashFactory $passwordHashFactory;

    public function __construct(protected readonly string $mode)
    {
        $this->passwordHashFactory = GeneralUtility::makeInstance(PasswordHashFactory::class);
    }

    /**
     * Generate plain and hashed recovery codes and return them as key/value
     */
    public function generateRecoveryCodes(): array
    {
        $plainCodes = $this->generatePlainRecoveryCodes();
        return array_combine($plainCodes, $this->generatedHashedRecoveryCodes($plainCodes));
    }

    /**
     * Generate given amount of plain recovery codes with the given length
     *
     * @return list<non-empty-string>
     */
    public function generatePlainRecoveryCodes(int $length = 8, int $quantity = 8): array
    {
        if ($length < self::MIN_LENGTH) {
            throw new \InvalidArgumentException(
                $length . ' is not allowed as length for recovery codes. Must be at least ' . self::MIN_LENGTH,
                1613666803
            );
        }

        /** @var list<non-empty-string> $codes */
        $codes = [];
        while ($quantity >= 1 && count($codes) < $quantity) {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= (string)random_int(0, 9);
            }
            // Prevent duplicate codes which is however very unlikely to happen
            if (!in_array($code, $codes, true)) {
                $codes[] = $code;
            }
        }
        return $codes;
    }

    /**
     * Hash the given plain recovery codes with the default hash instance and return them
     */
    public function generatedHashedRecoveryCodes(array $codes): array
    {
        // Use the current default hash instance for hashing the recovery codes
        $hashInstance = $this->passwordHashFactory->getDefaultHashInstance($this->mode);

        foreach ($codes as &$code) {
            $code = $hashInstance->getHashedPassword($code);
        }
        unset($code);
        return $codes;
    }

    /**
     * Compare given recovery code against all hashed codes and
     * unset the corresponding code on success.
     */
    public function verifyRecoveryCode(string $recoveryCode, array &$codes): bool
    {
        if ($codes === []) {
            return false;
        }

        // Get the hash instance which was initially used to generate these codes.
        // This could differ from the current default hash instance. We however only need
        // to check the first code since recovery codes can not be generated individually.
        $hasInstance = $this->passwordHashFactory->get(reset($codes), $this->mode);

        foreach ($codes as $key => $code) {
            // Compare hashed codes
            if ($hasInstance->checkPassword($recoveryCode, $code)) {
                // Unset the matching code
                unset($codes[$key]);
                return true;
            }
        }
        return false;
    }
}
