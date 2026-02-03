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

use TYPO3\CMS\Core\Exception\InvalidPasswordRulesException;

/**
 * This is an interface that has to be used by all password generators.
 *
 * Each password generator needs to implement the generate method that returns the generated password.
 * In case an invalid option/configuration is passed to the generator, an InvalidPasswordRulesException
 * or LogicException needs to be thrown.
 */
interface PasswordGeneratorInterface
{
    /**
     * @throws InvalidPasswordRulesException
     */
    public function generate(array $options): string;
}
