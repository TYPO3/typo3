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

namespace TYPO3\CMS\Core\PasswordPolicy\Event;

use TYPO3\CMS\Core\PasswordPolicy\Validator\Dto\ContextData;

/**
 * Event is dispatched before the `ContextData` DTO is passed to the password policy validator.
 *
 * Note, that the `$userData` array will include user data available from the initiating class only.
 * Event listeners should therefore always consider the initiating class name when accessing data
 * from `getUserData()`.
 */
final class EnrichPasswordValidationContextDataEvent
{
    public function __construct(
        protected readonly ContextData $contextData,
        protected readonly array $userData,
        protected readonly string $initiatingClass
    ) {
    }

    public function getContextData(): ContextData
    {
        return $this->contextData;
    }

    public function getUserData(): array
    {
        return $this->userData;
    }

    public function getInitiatingClass(): string
    {
        return $this->initiatingClass;
    }
}
