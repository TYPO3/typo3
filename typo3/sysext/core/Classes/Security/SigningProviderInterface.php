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
 * @internal
 */
interface SigningProviderInterface
{
    /**
     * Provides a signing secret independently of any name or identifier.
     * In case there is none, the corresponding provider has to create a new one.
     */
    public function provideSigningSecret(): SigningSecretInterface;

    /**
     * Finds a signing secret for a given name
     */
    public function findSigningSecret(string $name): ?SigningSecretInterface;

    /**
     * Revokes a signing secret for a given name
     * (providers without revocation functionality use an empty method body)
     */
    public function revokeSigningSecret(string $name): void;
}
