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

namespace TYPO3\CMS\Core\Attribute;

/**
 * Registers an authenticator of the authentication pipeline for a realm.
 * The class must implement AuthenticatorInterface. The attribute can be
 * repeated to serve several realms, each with a priority of its own.
 *
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication pipeline groundwork
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final readonly class AsAuthenticator
{
    public const string TAG_NAME = 'core.authenticator';

    public function __construct(
        /**
         * "backend", "frontend" for the frontend of every site, or the
         * identifier of a custom realm such as "api"
         */
        public string $realm,
        /**
         * Authenticators with a higher priority are asked first
         */
        public int $priority = 0,
    ) {}
}
