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
 * Class with context data used in password validators. Uses internally an array with key/value pairs to store data.
 * Extensions authors using this class should use `setData()` and `getData()` to write or read custom data used in
 * custom password validators.
 *
 * @internal only to be used within ext:core, not part of TYPO3 Core API.
 */
class ContextData
{
    protected array $data = [];

    public function __construct(
        string $loginMode = 'BE',
        string $currentPasswordHash = '',
        string $newUsername = '',
        string $newUserFirstName = '',
        string $newUserLastName = '',
        string $newUserFullName = '',
    ) {
        $this->data['loginMode'] = $loginMode;
        $this->data['currentPasswordHash'] = $currentPasswordHash;
        $this->data['newUsername'] = $newUsername;
        $this->data['newUserFirstName'] = $newUserFirstName;
        $this->data['newUserLastName'] = $newUserLastName;
        $this->data['newUserFullName'] = $newUserFullName;
    }

    public function getLoginMode(): string
    {
        return $this->getData('loginMode');
    }

    public function getCurrentPasswordHash(): string
    {
        return $this->getData('currentPasswordHash');
    }

    public function getNewUsername(): string
    {
        return $this->getData('newUsername');
    }

    public function getNewUserFirstName(): string
    {
        return $this->getData('newUserFirstName');
    }

    public function getNewUserLastName(): string
    {
        return $this->getData('newUserLastName');
    }

    public function getNewUserFullName(): string
    {
        return $this->getData('newUserFullName');
    }

    public function getData(string $key): string
    {
        return $this->data[$key] ?? '';
    }

    public function setData(string $key, string $value): void
    {
        $this->data[$key] = $value;
    }
}
