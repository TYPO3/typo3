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

use Psr\Http\Message\ResponseInterface;

/**
 * Authentication outcome demanding an interactive step from the caller,
 * carrying the PSR-7 response that initiates it. A redirect to an identity
 * provider or multi-factor view, a WebAuthn ceremony, or a plain 401.
 *
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication pipeline groundwork
 */
final readonly class Challenge implements AuthenticationResult
{
    public function __construct(
        public ResponseInterface $response,
    ) {}
}
