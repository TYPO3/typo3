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

namespace TYPO3\CMS\Beuser\Domain\Model;

/**
 * Demand filter for listings
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class Demand
{
    public const ALL = 0;

    public const USERTYPE_ADMINONLY = 1;
    public const USERTYPE_USERONLY = 2;

    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 2;

    public const LOGIN_SOME = 1;
    public const LOGIN_NONE = 2;

    protected string $userName = '';
    protected int $userType = self::ALL;
    protected int $status = self::ALL;
    protected int $logins = 0;
    protected int $backendUserGroup = 0;

    public static function fromUc(array $uc): self
    {
        $demand = new self();
        $demand->userName = (string)($uc['userName'] ?? '');
        $demand->userType = (int)($uc['userType'] ?? 0);
        $demand->status = (int)($uc['status'] ?? 0);
        $demand->logins = (int)($uc['logins'] ?? 0);
        $demand->backendUserGroup = (int)($uc['backendUserGroup'] ?? 0);
        return $demand;
    }

    public function forUc(): array
    {
        return [
            'userName' => $this->getUserName(),
            'userType' => $this->getUserType(),
            'status' => $this->getStatus(),
            'logins' => $this->getLogins(),
            'backendUserGroup' => $this->getBackendUserGroup(),
        ];
    }

    public function setUserName(string $userName): void
    {
        $this->userName = $userName;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function setUserType(int $userType): void
    {
        $this->userType = $userType;
    }

    public function getUserType(): int
    {
        return $this->userType;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setLogins(int $logins): void
    {
        $this->logins = $logins;
    }

    public function getLogins(): int
    {
        return $this->logins;
    }

    public function setBackendUserGroup(int $backendUserGroup): void
    {
        $this->backendUserGroup = $backendUserGroup;
    }

    public function getBackendUserGroup(): int
    {
        return $this->backendUserGroup;
    }
}
