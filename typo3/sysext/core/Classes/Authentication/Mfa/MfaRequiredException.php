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

use TYPO3\CMS\Core\Exception;

/**
 * This exception is thrown during the authentication process
 * when a user has successfully passed his first authentication
 * method (e.g. via username+password), but is required to also
 * pass multi-factor authentication (e.g. one-time password).
 */
class MfaRequiredException extends Exception
{
    private MfaProviderManifestInterface $provider;

    public function __construct(MfaProviderManifestInterface $provider, $code = 0, $message = '', \Throwable $previous = null)
    {
        $this->provider = $provider;
        parent::__construct($message, $code, $previous);
    }

    public function getProvider(): MfaProviderManifestInterface
    {
        return $this->provider;
    }
}
