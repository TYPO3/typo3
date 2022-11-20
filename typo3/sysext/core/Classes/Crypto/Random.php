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

namespace TYPO3\CMS\Core\Crypto;

use TYPO3\CMS\Core\Exception\InvalidPasswordRulesException;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Crypto safe pseudo-random value generation
 */
class Random
{
    private const DEFAULT_PASSWORD_LEGNTH = 16;
    private const LOWERCASE_CHARACTERS = 'abcdefghijklmnopqrstuvwxyz';
    private const UPPERCASE_CHARACTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const SPECIAL_CHARACTERS = '!"#$%&\'()*+,-./:;<=>?@[\]^_`{|}~';
    private const DIGIT_CHARACTERS = '1234567890';

    /**
     * Generates cryptographic secure pseudo-random bytes
     */
    public function generateRandomBytes(int $length): string
    {
        return random_bytes($length);
    }

    /**
     * Generates cryptographic secure pseudo-random integers
     */
    public function generateRandomInteger(int $min, int $max): int
    {
        return random_int($min, $max);
    }

    /**
     * Generates cryptographic secure pseudo-random hex string
     */
    public function generateRandomHexString(int $length): string
    {
        return substr(bin2hex($this->generateRandomBytes((int)(($length + 1) / 2))), 0, $length);
    }

    /**
     * Generates cryptographic secure pseudo-random password based on given password rules
     *
     * @internal Only to be used within TYPO3. Might change in the future.
     */
    public function generateRandomPassword(array $passwordRules): string
    {
        $passwordLength = (int)($passwordRules['length'] ?? self::DEFAULT_PASSWORD_LEGNTH);
        if ($passwordLength < 8) {
            throw new InvalidPasswordRulesException(
                'Password rules are invalid. Length must be at least 8.',
                1667557900
            );
        }

        $password = '';

        if ($passwordRules['random'] ?? false) {
            $password = match ((string)$passwordRules['random']) {
                'hex' => $this->generateRandomHexString($passwordLength),
                'base64' => $this->generateRandomBase64String($passwordLength),
                default => throw new InvalidPasswordRulesException('Invalid value for special password rule \'random\'. Valid options are: \'hex\' and \'base64\'', 1667557901),
            };
        } else {
            $characters = [];
            $characterSets = [];
            if (filter_var($passwordRules['lowerCaseCharacters'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) {
                $characters = array_merge($characters, str_split(self::LOWERCASE_CHARACTERS));
                $characterSets[] = self::LOWERCASE_CHARACTERS;
            }
            if (filter_var($passwordRules['upperCaseCharacters'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) {
                $characters = array_merge($characters, str_split(self::UPPERCASE_CHARACTERS));
                $characterSets[] = self::UPPERCASE_CHARACTERS;
            }
            if (filter_var($passwordRules['digitCharacters'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) {
                $characters = array_merge($characters, str_split(self::DIGIT_CHARACTERS));
                $characterSets[] = self::DIGIT_CHARACTERS;
            }
            if (filter_var($passwordRules['specialCharacters'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) {
                $characters = array_merge($characters, str_split(self::SPECIAL_CHARACTERS));
                $characterSets[] = self::SPECIAL_CHARACTERS;
            }

            if ($characterSets === []) {
                throw new InvalidPasswordRulesException(
                    'Password rules are invalid. At least one character set must be allowed.',
                    1667557902
                );
            }

            foreach ($characterSets as $characterSet) {
                $password .= $characterSet[random_int(0, strlen($characterSet) - 1)];
            }

            $charactersCount = count($characters);
            for ($i = 0; $i < $passwordLength - count($characterSets); $i++) {
                $password .= $characters[random_int(0, $charactersCount - 1)];
            }

            str_shuffle($password);
        }

        return $password;
    }

    /**
     * Generates cryptographic secure pseudo-random base64 string
     */
    protected function generateRandomBase64String(int $length): string
    {
        return substr(StringUtility::base64urlEncode($this->generateRandomBytes((int)ceil(($length / 4) * 3))), 0, $length);
    }
}
