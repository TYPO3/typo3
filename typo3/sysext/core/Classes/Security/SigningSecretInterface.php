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
 * Provides the value that is used as secret in a cryptographic signing process.
 *
 * @internal
 */
interface SigningSecretInterface
{
    /**
     * Returns a public identifier of the secret.
     */
    public function getSigningIdentifier(): SecretIdentifier;

    /**
     * Returns secret used for signing messages.
     */
    public function getSigningSecret(): string;
}
