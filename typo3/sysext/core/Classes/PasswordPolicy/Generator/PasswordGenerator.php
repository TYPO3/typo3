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

namespace TYPO3\CMS\Core\PasswordPolicy\Generator;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Exception\InvalidPasswordRulesException;

/**
 * @internal only to be used within ext:core, not part of TYPO3 Core API.
 */
#[Autoconfigure(public: true)]
final readonly class PasswordGenerator implements PasswordGeneratorInterface
{
    public function __construct(private Random $random) {}

    /**
     * @throws InvalidPasswordRulesException
     */
    public function generate(array $options): string
    {
        return $this->random->generateRandomPassword($options);
    }
}
