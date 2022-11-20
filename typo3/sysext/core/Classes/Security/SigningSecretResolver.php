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

namespace TYPO3\CMS\Core\Security;

/**
 * Resolves SigningSecretInterface items.
 *
 * @internal This class with change!
 */
class SigningSecretResolver
{
    /**
     * @var array<string, SigningProviderInterface>
     */
    protected array $providers;

    public function __construct(array $providers)
    {
        $this->providers = array_filter(
            $providers,
            static fn ($provider) => $provider instanceof SigningProviderInterface
        );
    }

    /**
     * Resolves a signing provider by its type (e.g. `NoncePool` from type `'nonce'`)
     */
    public function findByType(string $type): ?SigningProviderInterface
    {
        return $this->providers[$type] ?? null;
    }

    /**
     * Resolves a specific signing secret by its public identifier
     * (e.g. specific `Nonce` from `NoncePool` by given public identifier "nonce:[public-name]")
     */
    public function findByIdentifier(SecretIdentifier $identifier): ?SigningSecretInterface
    {
        if (!isset($this->providers[$identifier->type])) {
            return null;
        }
        return $this->providers[$identifier->type]->findSigningSecret($identifier->name);
    }

    /**
     * Revokes a specific signing secret.
     */
    public function revokeIdentifier(SecretIdentifier $identifier): void
    {
        if (!isset($this->providers[$identifier->type])) {
            return;
        }
        $this->providers[$identifier->type]->revokeSigningSecret($identifier->name);
    }
}
