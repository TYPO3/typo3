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

namespace TYPO3\CMS\Install\Updates;

class Confirmation
{
    public function __construct(
        protected readonly string $title,
        protected readonly string $message,
        protected readonly bool $defaultValue = false,
        protected readonly string $confirm = 'Yes, execute',
        protected readonly string $deny = 'No, do not execute',
        protected readonly bool $required = false
    ) {}

    public function getConfirm(): string
    {
        return $this->confirm;
    }

    public function getDeny(): string
    {
        return $this->deny;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getDefaultValue(): bool
    {
        return $this->defaultValue;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
