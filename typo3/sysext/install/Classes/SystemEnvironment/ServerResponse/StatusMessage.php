<?php

declare(strict_types = 1);

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

namespace TYPO3\CMS\Install\SystemEnvironment\ServerResponse;

/**
 * @internal should only be used from within TYPO3 Core
 */
class StatusMessage
{
    /**
     * @var string
     */
    protected $message;

    /**
     * @var string[]
     */
    protected $values;

    public function __construct(string $message, string ...$values)
    {
        $this->message = $message;
        $this->values = $values;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return string[]
     */
    public function getValues(): array
    {
        return $this->values;
    }
}
