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

use TYPO3\CMS\Backend\Authentication\PasswordReset;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Attribute as Extbase;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Model for backend user
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class BackendUser extends AbstractEntity
{
    #[Extbase\Validate(['validator' => 'NotEmpty'])]
    protected string $userName = '';

    /**
     * @var ObjectStorage<BackendUserGroup>
     */
    protected ObjectStorage $backendUserGroups;

    /**
     * Comma separated list of uids in multi-select
     * Might retrieve the labels from TCA/DataMapper
     */
    protected string $allowedLanguages = '';

    protected string $dbMountPoints = '';
    protected string $description = '';
    protected string $fileMountPoints = '';
    protected bool $isAdministrator = false;
    protected bool $isDisabled = false;
    protected ?\DateTime $startDateAndTime = null;
    protected ?\DateTime $endDateAndTime = null;
    protected string $email = '';
    protected string $realName = '';
    protected ?\DateTime $lastLoginDateAndTime = null;

    public function __construct()
    {
        $this->initializeObject();
    }

    public function initializeObject(): void
    {
        $this->backendUserGroups = new ObjectStorage();
    }

    public function setAllowedLanguages(string $allowedLanguages): void
    {
        $this->allowedLanguages = $allowedLanguages;
    }

    public function getAllowedLanguages(): string
    {
        return $this->allowedLanguages;
    }

    public function setDbMountPoints(string $dbMountPoints): void
    {
        $this->dbMountPoints = $dbMountPoints;
    }

    public function getDbMountPoints(): string
    {
        return $this->dbMountPoints;
    }

    public function setFileMountPoints(string $fileMountPoints): void
    {
        $this->fileMountPoints = $fileMountPoints;
    }

    public function getFileMountPoints(): string
    {
        return $this->fileMountPoints;
    }

    /**
     * Check if user is active, not disabled
     */
    public function isActive(): bool
    {
        if ($this->getIsDisabled()) {
            return false;
        }
        $now = new \DateTime('now');
        return (!$this->getStartDateAndTime() && !$this->getEndDateAndTime()) || ($this->getStartDateAndTime() <= $now && (!$this->getEndDateAndTime() || $this->getEndDateAndTime() > $now));
    }

    public function setBackendUserGroups(ObjectStorage $backendUserGroups): void
    {
        $this->backendUserGroups = $backendUserGroups;
    }

    /**
     * @return ObjectStorage<BackendUserGroup>
     */
    public function getBackendUserGroups(): ObjectStorage
    {
        return $this->backendUserGroups;
    }

    /**
     * Check if user is currently logged in
     */
    public function isCurrentlyLoggedIn(): bool
    {
        return $this->getUid() === (int)($this->getBackendUser()->user['uid'] ?? 0);
    }

    /**
     * Check if the user is allowed to trigger a password reset
     *
     * Requirements:
     * 1. The user for which the password reset should be triggered is not the currently logged in user
     * 2. Password reset is enabled for the user (Email+Password are set)
     * 3. The currently logged in user is allowed to reset passwords in the backend (Enabled in user TSconfig)
     */
    public function isPasswordResetEnabled(): bool
    {
        return !$this->isCurrentlyLoggedIn()
            && GeneralUtility::makeInstance(PasswordReset::class)->isEnabledForUser((int)$this->getUid())
            && ($this->getBackendUser()->getTSConfig()['options.']['passwordReset'] ?? true);
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function setUserName(string $userName): void
    {
        $this->userName = $userName;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getIsAdministrator(): bool
    {
        return $this->isAdministrator;
    }

    public function setIsAdministrator(bool $isAdministrator): void
    {
        $this->isAdministrator = $isAdministrator;
    }

    public function getIsDisabled(): bool
    {
        return $this->isDisabled;
    }

    public function setIsDisabled(bool $isDisabled): void
    {
        $this->isDisabled = $isDisabled;
    }

    public function getStartDateAndTime(): ?\DateTime
    {
        return $this->startDateAndTime;
    }

    public function setStartDateAndTime(?\DateTime $dateAndTime = null): void
    {
        $this->startDateAndTime = $dateAndTime;
    }

    public function getEndDateAndTime(): ?\DateTime
    {
        return $this->endDateAndTime;
    }

    public function setEndDateAndTime(?\DateTime $dateAndTime = null): void
    {
        $this->endDateAndTime = $dateAndTime;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getRealName(): string
    {
        return $this->realName;
    }

    public function setRealName(string $name): void
    {
        $this->realName = $name;
    }

    public function getLastLoginDateAndTime(): ?\DateTime
    {
        return $this->lastLoginDateAndTime;
    }

    public function setLastLoginDateAndTime(?\DateTime $dateAndTime = null): void
    {
        $this->lastLoginDateAndTime = $dateAndTime;
    }

    public function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
