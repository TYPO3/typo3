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

namespace TYPO3\CMS\Core\Authentication\Pipeline;

/**
 * Explicitly failed authentication attempt. Loggable and countable
 * towards rate limits, with an optional message safe to show to users.
 *
 * The reason is a stable, machine-readable code such as
 * "invalid-credentials" or "invalid-token", which callers may map to a
 * response. It is never shown to users.
 *
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication pipeline groundwork
 */
final readonly class Failure implements AuthenticationResult
{
    public function __construct(
        public string $reason,
        public ?string $userFacingMessage = null,
    ) {}
}
