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

namespace TYPO3\CMS\FrontendLogin\Event;

use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Event that contains information about the password which was set, and is about to be stored in the database.
 *
 * Additional validation can happen here.
 */
final class PasswordChangeEvent implements StoppableEventInterface
{
    private bool $invalid = false;
    private ?string $errorMessage = null;

    public function __construct(
        private readonly array $user,
        private string $passwordHash,
        private readonly string $rawPassword
    ) {
    }

    public function getUser(): array
    {
        return $this->user;
    }

    public function getHashedPassword(): string
    {
        return $this->passwordHash;
    }

    public function setHashedPassword(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    public function getRawPassword(): string
    {
        return $this->rawPassword;
    }

    public function setAsInvalid(string $message): void
    {
        $this->invalid = true;
        $this->errorMessage = $message;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function isPropagationStopped(): bool
    {
        return $this->invalid;
    }
}
