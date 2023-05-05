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

namespace TYPO3\CMS\Backend\Security\SudoMode\Access;

/**
 * Representation of any granted access to a particular subject, having an expiration time.
 * The user successfully verified a previous `AccessClaim` by entering their password.
 *
 * @internal
 */
class AccessGrant implements \JsonSerializable
{
    public function __construct(
        public readonly AccessSubjectInterface $subject,
        public readonly int $expiration,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'subject' => $this->subject,
            'expiration' => $this->expiration,
        ];
    }
}
