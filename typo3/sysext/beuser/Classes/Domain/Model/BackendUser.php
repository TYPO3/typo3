<?php
namespace TYPO3\CMS\Beuser\Domain\Model;

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

/**
 * Model for backend user
 */
class BackendUser extends \TYPO3\CMS\Extbase\Domain\Model\BackendUser
{
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
    protected $description;

    /**
     * @var string
     */
    protected $fileMountPoints = '';

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Beuser\Domain\Model\BackendUserGroup>
     */
    protected $backendUserGroups;

    /**
     * @param string $allowedLanguages
     * @return void
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
     * @param string
     * @return void
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
     * @param string $fileMountPoints
     * @return void
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
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $backendUserGroups
     */
    public function setBackendUserGroups($backendUserGroups)
    {
        $this->backendUserGroups = $backendUserGroups;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
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
     * Gets the currently logged in backend user
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    public function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
