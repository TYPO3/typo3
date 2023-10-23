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

namespace TYPO3\CMS\Reactions\Model;

use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * An entity for a DB record of sys_reaction
 */
class ReactionInstruction
{
    public function __construct(
        protected array $record
    ) {}

    public function getUid(): int
    {
        return $this->record['uid'];
    }

    public function getName(): string
    {
        return $this->record['name'];
    }

    public function getType(): string
    {
        return $this->record['reaction_type'];
    }

    public function getIdentifier(): string
    {
        return $this->record['identifier'];
    }

    public function getImpersonateUser(): int
    {
        return $this->record['impersonate_user'];
    }

    public function isSecretValid(string $secret): bool
    {
        $hashInstance = GeneralUtility::makeInstance(PasswordHashFactory::class)->getDefaultHashInstance('BE');
        return $hashInstance->checkPassword($secret, $this->record['secret']);
    }

    public function toArray(): array
    {
        return $this->record;
    }
}
