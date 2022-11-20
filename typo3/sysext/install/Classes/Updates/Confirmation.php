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
    /**
     * @var bool
     */
    protected $defaultValue = false;

    /**
     * @var string
     */
    protected $title = '';

    /**
     * @var string
     */
    protected $message = '';

    /**
     * @var string
     */
    protected $confirm;

    /**
     * @var string
     */
    protected $deny;

    /**
     * @var bool
     */
    protected $required;

    public function __construct(
        string $title,
        string $message,
        bool $defaultValue = false,
        string $confirm = 'Yes, execute',
        string $deny = 'No, do not execute',
        bool $required = false
    ) {
        $this->title = $title;
        $this->message = $message;
        $this->defaultValue = $defaultValue;
        $this->confirm = $confirm;
        $this->deny = $deny;
        $this->required = $required;
    }

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
