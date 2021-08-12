<?php

declare(strict_types = 1);

/*
 * This file is part of the TYPO3 project.
 *
 * It is free software; you can redistribute it and/or modify it under the terms
 * of the MIT License (MIT). For the full copyright and license information,
 * please read the LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\Html;

use TYPO3\HtmlSanitizer\InitiatorInterface;

/**
 * Initiator for HTML sanitization process, forwarded to sanitizer and used during logging.
 *
 * @internal
 */
class SanitizerInitiator implements InitiatorInterface
{
    /**
     * @var string
     */
    protected $trace;

    public function __construct(string $trace)
    {
        $this->trace = $trace;
    }

    public function __toString(): string
    {
        return $this->trace;
    }
}
