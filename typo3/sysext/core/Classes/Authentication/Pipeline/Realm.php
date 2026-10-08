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

use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

/**
 * Describes a security zone authentication is performed for. The TYPO3
 * backend, the frontend of a site, or a custom realm such as a token based
 * API.
 *
 * Authenticators are registered for the realms they serve, so that
 * credentials meant for one realm never authenticate another.
 *
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication pipeline groundwork
 */
final readonly class Realm
{
    private const string BACKEND = 'backend';
    private const string FRONTEND = 'frontend';

    private function __construct(
        public string $identifier,
    ) {}

    public static function backend(): self
    {
        return new self(self::BACKEND);
    }

    /**
     * The frontend realm of a site, or the general frontend realm if the
     * request has no site configuration (no site or a NullSite).
     */
    public static function frontend(?SiteInterface $site = null): self
    {
        return new self($site instanceof Site ? self::FRONTEND . ':' . $site->getIdentifier() : self::FRONTEND);
    }

    /**
     * A realm of its own, e.g. for a token based API. Like the backend realm,
     * a custom realm authenticates backend users. The identifiers of the
     * backend and frontend realms are reserved.
     */
    public static function custom(string $identifier): self
    {
        if (preg_match('/^[a-z][a-z0-9_.-]*$/', $identifier) !== 1) {
            throw new \InvalidArgumentException(
                'A realm identifier must start with a lowercase letter and contain only lowercase letters, digits, ".", "_" and "-".',
                1791491201
            );
        }
        if ($identifier === self::BACKEND || $identifier === self::FRONTEND) {
            throw new \InvalidArgumentException(
                'The realm identifier "' . $identifier . '" is reserved.',
                1791491202
            );
        }
        return new self($identifier);
    }

    /**
     * The realm an authenticator is registered for: "backend", "frontend"
     * for the frontend of every site, or a custom realm. The frontend realm
     * of a single site can not be registered.
     */
    public static function fromRegistration(string $identifier): self
    {
        if ($identifier === self::BACKEND) {
            return self::backend();
        }
        if ($identifier === self::FRONTEND) {
            return self::frontend();
        }
        if (self::isFrontendIdentifier($identifier)) {
            throw new \InvalidArgumentException(
                'Authenticators serve the frontend of every site and are registered for the realm "frontend", the realm "' . $identifier . '" can not be registered.',
                1792942004
            );
        }
        return self::custom($identifier);
    }

    /**
     * The realm identifier the authenticators of this realm are registered
     * for, "frontend" for the frontend realm of every site.
     */
    public function getRegistrationIdentifier(): string
    {
        return $this->isFrontend() ? self::FRONTEND : $this->identifier;
    }

    public function isBackend(): bool
    {
        return $this->identifier === self::BACKEND;
    }

    public function isFrontend(): bool
    {
        return self::isFrontendIdentifier($this->identifier);
    }

    /**
     * The frontend realm and the frontend realms of sites authenticate
     * frontend users, every other realm authenticates backend users.
     */
    public static function isFrontendIdentifier(string $identifier): bool
    {
        return $identifier === self::FRONTEND || str_starts_with($identifier, self::FRONTEND . ':');
    }
}
