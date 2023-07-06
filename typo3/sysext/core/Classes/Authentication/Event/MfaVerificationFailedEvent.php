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

namespace TYPO3\CMS\Core\Authentication\Event;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderManifestInterface;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderPropertyManager;

/**
 * Event fired after MFA verification failed.
 */
final class MfaVerificationFailedEvent extends AbstractAuthenticationFailedEvent
{
    public function __construct(
        private readonly ServerRequestInterface $request,
        private readonly MfaProviderPropertyManager $propertyManager,
        private readonly MfaProviderManifestInterface $mfaProvider,
    ) {
        parent::__construct($this->request);
    }

    public function getUser(): AbstractUserAuthentication
    {
        return $this->propertyManager->getUser();
    }

    public function getProviderIdentifier(): string
    {
        return $this->mfaProvider->getIdentifier();
    }

    public function getProviderProperties(): array
    {
        return $this->propertyManager->getProperties();
    }

    public function isProviderLocked(): bool
    {
        return $this->mfaProvider->isLocked($this->propertyManager);
    }
}
