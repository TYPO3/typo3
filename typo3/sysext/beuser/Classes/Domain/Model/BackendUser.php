<?php

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
use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Model for backend user
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class BackendUser extends AbstractEntity
{
    /**
     * @var string
     * @Extbase\Validate("NotEmpty")
     */
    protected $userName = '';

    /**
     * @var ObjectStorage<BackendUserGroup>
     */
    protected $backendUserGroups;

    /**
     * Comma separated list of uids in multi-select
     * Might retrieve the labels from TCA/DataMapper
     *
     * @var string
     */
    protected $allowedLanguages = '';

    /**
     * @var string
     */
    protected $dbMountPoints = '';

    /**
     * @var string
     */
    protected $description = '';

    /**
     * @var string
     */
    protected $fileMountPoints = '';

    /**
     * @var bool
     */
    protected $isAdministrator = false;

    /**
     * @var bool
     */
    protected $isDisabled = false;

    /**
     * @var \DateTime|null
     */
    protected $startDateAndTime;

    /**
     * @var \DateTime|null
     */
    protected $endDateAndTime;

    /**
     * @var string
     */
    protected $email = '';

    /**
     * @var string
     */
    protected $realName = '';

    /**
     * @var \DateTime|null
     */
    protected $lastLoginDateAndTime;

    /**
     * @param string $allowedLanguages
     */
    public function setAllowedLanguages($allowedLanguages)
    {
        $this->allowedLanguages = $allowedLanguages;
    }

    /**
     * @return string
     */
    public function getAllowedLanguages()
    {
        return $this->allowedLanguages;
    }

    /**
     * @param string $dbMountPoints
     */
    public function setDbMountPoints($dbMountPoints)
    {
        $this->dbMountPoints = $dbMountPoints;
    }

    /**
     * @return string
     */
    public function getDbMountPoints()
    {
        return $this->dbMountPoints;
    }

    /**
     * @param string $fileMountPoints
     */
    public function setFileMountPoints($fileMountPoints)
    {
        $this->fileMountPoints = $fileMountPoints;
    }

    /**
     * @return string
     */
    public function getFileMountPoints()
    {
        return $this->fileMountPoints;
    }

    /**
     * Check if user is active, not disabled
     *
     * @return bool
     */
    public function isActive()
    {
        if ($this->getIsDisabled()) {
            return false;
        }
        $now = new \DateTime('now');
        return !$this->getStartDateAndTime() && !$this->getEndDateAndTime() || $this->getStartDateAndTime() <= $now && (!$this->getEndDateAndTime() || $this->getEndDateAndTime() > $now);
    }

    /**
     * @param ObjectStorage<BackendUserGroup> $backendUserGroups
     */
    public function setBackendUserGroups($backendUserGroups)
    {
        $this->backendUserGroups = $backendUserGroups;
    }

    /**
     * @return ObjectStorage<BackendUserGroup>
     */
    public function getBackendUserGroups()
    {
        return $this->backendUserGroups;
    }

    /**
     * Check if user is currently logged in
     *
     * @return bool
     */
    public function isCurrentlyLoggedIn()
    {
        return $this->getUid() === (int)$this->getBackendUser()->user['uid'];
    }

    /**
     * Check if the user is allowed to trigger a password reset
     *
     * Requirements:
     * 1. The user for which the password reset should be triggered is not the currently logged in user
     * 2. Password reset is enabled for the user (Email+Password are set)
     * 3. The currently logged in user is allowed to reset passwords in the backend (Enabled in user TSconfig)
     *
     * @return bool
     */
    public function isPasswordResetEnabled(): bool
    {
        return !$this->isCurrentlyLoggedIn()
            && GeneralUtility::makeInstance(PasswordReset::class)->isEnabledForUser((int)$this->getUid())
            && ($this->getBackendUser()->getTSConfig()['options.']['passwordReset'] ?? true);
    }

    /**
     * Gets the user name.
     *
     * @return string the user name, will not be empty
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * Sets the user name.
     *
     * @param string $userName the user name to set, must not be empty
     */
    public function setUserName($userName)
    {
        $this->userName = $userName;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Checks whether this user is an administrator.
     *
     * @return bool whether this user is an administrator
     */
    public function getIsAdministrator()
    {
        return $this->isAdministrator;
    }

    /**
     * Sets whether this user should be an administrator.
     *
     * @param bool $isAdministrator whether this user should be an administrator
     */
    public function setIsAdministrator($isAdministrator)
    {
        $this->isAdministrator = $isAdministrator;
    }

    /**
     * Checks whether this user is disabled.
     *
     * @return bool whether this user is disabled
     */
    public function getIsDisabled()
    {
        return $this->isDisabled;
    }

    /**
     * Sets whether this user is disabled.
     *
     * @param bool $isDisabled whether this user is disabled
     */
    public function setIsDisabled($isDisabled)
    {
        $this->isDisabled = $isDisabled;
    }

    /**
     * Returns the point in time from which this user is enabled.
     *
     * @return \DateTime|null the start date and time
     */
    public function getStartDateAndTime()
    {
        return $this->startDateAndTime;
    }

    /**
     * Sets the point in time from which this user is enabled.
     *
     * @param \DateTime|null $dateAndTime the start date and time
     */
    public function setStartDateAndTime(\DateTime $dateAndTime = null)
    {
        $this->startDateAndTime = $dateAndTime;
    }

    /**
     * Returns the point in time before which this user is enabled.
     *
     * @return \DateTime|null the end date and time
     */
    public function getEndDateAndTime()
    {
        return $this->endDateAndTime;
    }

    /**
     * Sets the point in time before which this user is enabled.
     *
     * @param \DateTime|null $dateAndTime the end date and time
     */
    public function setEndDateAndTime(\DateTime $dateAndTime = null)
    {
        $this->endDateAndTime = $dateAndTime;
    }

    /**
     * Gets the e-mail address of this user.
     *
     * @return string the e-mail address, might be empty
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Sets the e-mail address of this user.
     *
     * @param string $email the e-mail address, may be empty
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Returns this user's real name.
     *
     * @return string the real name. might be empty
     */
    public function getRealName()
    {
        return $this->realName;
    }

    /**
     * Sets this user's real name.
     *
     * @param string $name the user's real name, may be empty.
     */
    public function setRealName($name)
    {
        $this->realName = $name;
    }

    /**
     * Checks whether this user is currently activated.
     *
     * This function takes the "disabled" flag, the start date/time and the end date/time into account.
     *
     * @return bool whether this user is currently activated
     */
    public function isActivated()
    {
        return !$this->getIsDisabled() && $this->isActivatedViaStartDateAndTime() && $this->isActivatedViaEndDateAndTime();
    }

    /**
     * Checks whether this user is activated as far as the start date and time is concerned.
     *
     * @return bool whether this user is activated as far as the start date and time is concerned
     */
    protected function isActivatedViaStartDateAndTime()
    {
        if ($this->getStartDateAndTime() === null) {
            return true;
        }
        $now = new \DateTime('now');
        return $this->getStartDateAndTime() <= $now;
    }

    /**
     * Checks whether this user is activated as far as the end date and time is concerned.
     *
     * @return bool whether this user is activated as far as the end date and time is concerned
     */
    protected function isActivatedViaEndDateAndTime()
    {
        if ($this->getEndDateAndTime() === null) {
            return true;
        }
        $now = new \DateTime('now');
        return $now <= $this->getEndDateAndTime();
    }

    /**
     * Gets this user's last login date and time.
     *
     * @return \DateTime|null this user's last login date and time, will be NULL if this user has never logged in before
     */
    public function getLastLoginDateAndTime()
    {
        return $this->lastLoginDateAndTime;
    }

    /**
     * Sets this user's last login date and time.
     *
     * @param \DateTime|null $dateAndTime this user's last login date and time
     */
    public function setLastLoginDateAndTime(\DateTime $dateAndTime = null)
    {
        $this->lastLoginDateAndTime = $dateAndTime;
    }

    /**
     * Gets the currently logged in backend user
     */
    public function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
