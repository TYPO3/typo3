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

namespace TYPO3\CMS\Core\PasswordPolicy\Validator\Dto;

/**
 * Class with context data used in password validators
 */
class ContextData
{
    public function __construct(
        protected string $loginMode = 'BE',
        protected string $currentPasswordHash = '',
        protected string $newUsername = '',
        protected string $newUserFirstName = '',
        protected string $newUserLastName = '',
        protected string $newUserFullName = '',
    ) {
    }

    public function getLoginMode(): string
    {
        return $this->loginMode;
    }

    public function setLoginMode(string $loginMode): void
    {
        $this->loginMode = $loginMode;
    }

    public function getCurrentPasswordHash(): string
    {
        return $this->currentPasswordHash;
    }

    public function setCurrentPasswordHash(string $currentPasswordHash): void
    {
        $this->currentPasswordHash = $currentPasswordHash;
    }

    public function getNewUsername(): string
    {
        return $this->newUsername;
    }

    public function setNewUsername(string $newUsername): void
    {
        $this->newUsername = $newUsername;
    }

    public function getNewUserFirstName(): string
    {
        return $this->newUserFirstName;
    }

    public function setNewUserFirstName(string $newUserFirstName): void
    {
        $this->newUserFirstName = $newUserFirstName;
    }

    public function getNewUserLastName(): string
    {
        return $this->newUserLastName;
    }

    public function setNewUserLastName(string $newUserLastName): void
    {
        $this->newUserLastName = $newUserLastName;
    }

    public function getNewUserFullName(): string
    {
        return $this->newUserFullName;
    }

    public function setNewUserFullName(string $newUserFullName): void
    {
        $this->newUserFullName = $newUserFullName;
    }
}
