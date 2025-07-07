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

namespace TYPO3\CMS\Backend\Security\SudoMode\Event;

use TYPO3\CMS\Backend\Security\SudoMode\Access\AccessClaim;

final class SudoModeVerifyEvent
{
    private bool $verified = false;

    public function __construct(
        private readonly AccessClaim $claim,
        #[\SensitiveParameter]
        private readonly string $password,
        private readonly bool $useInstallToolPassword,
    ) {}

    public function getClaim(): AccessClaim
    {
        return $this->claim;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function isUseInstallToolPassword(): bool
    {
        return $this->useInstallToolPassword;
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function setVerified(bool $verified): void
    {
        $this->verified = $verified;
    }
}
