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

namespace TYPO3\CMS\Backend\Security\SudoMode\Exception;

use TYPO3\CMS\Backend\Security\SudoMode\Access\AccessClaim;

/**
 * Exception that signals that the verification process was successful, and that
 * the user shall be redirected to the URI, that has been requested originally.
 *
 * @internal
 */
final class VerificationRequiredException extends \RuntimeException
{
    protected AccessClaim $claim;

    public function withClaim(AccessClaim $bundle): self
    {
        $this->claim = $bundle;
        return $this;
    }

    public function getClaim(): AccessClaim
    {
        return $this->claim;
    }
}
