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

namespace TYPO3\CMS\Extbase\Authorization;

use TYPO3\CMS\Extbase\Attribute\Authorize;

/**
 * Immutable value object representing the result of an authorization check
 */
final readonly class AuthorizationResult
{
    private function __construct(
        public bool $authorized,
        public ?AuthorizationFailureReason $failureReason = null,
        public ?Authorize $failedAttribute = null,
    ) {}

    public static function allowed(): self
    {
        return new self(authorized: true);
    }

    public static function denied(AuthorizationFailureReason $reason, Authorize $attribute): self
    {
        return new self(
            authorized: false,
            failureReason: $reason,
            failedAttribute: $attribute
        );
    }

    public function isAllowed(): bool
    {
        return $this->authorized;
    }

    public function isDenied(): bool
    {
        return !$this->authorized;
    }
}
