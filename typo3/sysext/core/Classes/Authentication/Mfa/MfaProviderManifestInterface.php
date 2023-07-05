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

/**
 * Annotated information about the MFA provider – used in various views
 *
 * @internal should only be used by the TYPO3 Core
 */
interface MfaProviderManifestInterface extends MfaProviderInterface
{
    /**
     * Unique provider identifier
     */
    public function getIdentifier(): string;

    /**
     * The title of the provider
     */
    public function getTitle(): string;

    /**
     * A short description about the provider
     */
    public function getDescription(): string;

    /**
     * Instructions to be displayed in the setup view
     */
    public function getSetupInstructions(): string;

    /**
     * The icon identifier for this provider
     */
    public function getIconIdentifier(): string;

    /**
     * Whether the provider is allowed to be set as default
     */
    public function isDefaultProviderAllowed(): bool;
}
