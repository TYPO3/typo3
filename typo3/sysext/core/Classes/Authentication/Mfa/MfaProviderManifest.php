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

namespace TYPO3\CMS\Core\Authentication\Mfa;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Adapter for MFA providers
 *
 * @internal should only be used by the TYPO3 Core
 */
final class MfaProviderManifest implements MfaProviderManifestInterface
{
    private ?MfaProviderInterface $instance = null;

    public function __construct(
        private readonly string $identifier,
        private readonly string $title,
        private readonly string $description,
        private readonly string $setupInstructions,
        private readonly string $iconIdentifier,
        private readonly bool $isDefaultProviderAllowed,
        private readonly string $serviceName,
        private readonly ContainerInterface $container
    ) {}

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getIconIdentifier(): string
    {
        return $this->iconIdentifier;
    }

    public function getSetupInstructions(): string
    {
        return $this->setupInstructions;
    }

    public function isDefaultProviderAllowed(): bool
    {
        return $this->isDefaultProviderAllowed;
    }

    public function canProcess(ServerRequestInterface $request): bool
    {
        return $this->getInstance()->canProcess($request);
    }

    public function isActive(MfaProviderPropertyManager $propertyManager): bool
    {
        return $this->getInstance()->isActive($propertyManager);
    }

    public function isLocked(MfaProviderPropertyManager $propertyManager): bool
    {
        return $this->getInstance()->isLocked($propertyManager);
    }

    public function verify(ServerRequestInterface $request, MfaProviderPropertyManager $propertyManager): bool
    {
        return $this->getInstance()->verify($request, $propertyManager);
    }

    public function handleRequest(
        ServerRequestInterface $request,
        MfaProviderPropertyManager $propertyManager,
        MfaViewType $type
    ): ResponseInterface {
        return $this->getInstance()->handleRequest($request, $propertyManager, $type);
    }

    public function activate(ServerRequestInterface $request, MfaProviderPropertyManager $propertyManager): bool
    {
        return $this->getInstance()->activate($request, $propertyManager);
    }

    public function deactivate(ServerRequestInterface $request, MfaProviderPropertyManager $propertyManager): bool
    {
        return $this->getInstance()->deactivate($request, $propertyManager);
    }

    public function unlock(ServerRequestInterface $request, MfaProviderPropertyManager $propertyManager): bool
    {
        return $this->getInstance()->unlock($request, $propertyManager);
    }

    public function update(ServerRequestInterface $request, MfaProviderPropertyManager $propertyManager): bool
    {
        return $this->getInstance()->update($request, $propertyManager);
    }

    private function getInstance(): MfaProviderInterface
    {
        return $this->instance ?? $this->createInstance();
    }

    private function createInstance(): MfaProviderInterface
    {
        $this->instance = $this->container->get($this->serviceName);
        return $this->instance;
    }
}
