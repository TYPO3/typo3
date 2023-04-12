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
 * Representation of a claim (request) to a specific subject, before being granted.
 * The user still has to verify, that this claim is correct, by entering their password.
 *
 * @internal
 */
class AccessClaim implements \JsonSerializable
{
    public readonly string $id;
    public function __construct(
        public readonly AccessSubjectInterface $subject,
        public readonly ServerRequestInstruction $instruction,
        public readonly int $expiration,
        string $id = null,
    ) {
        $this->id = $id ?? bin2hex(random_bytes(20));
    }

    public function jsonSerialize(): array
    {
        return [
            'subject' => $this->subject,
            'instruction' => $this->instruction,
            'expiration' => $this->expiration,
            'id' => $this->id,
        ];
    }
}
