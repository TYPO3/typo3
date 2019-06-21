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
 * Demand filter for listings
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class Demand extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var int
     */
    const ALL = 0;
    /**
     * @var int
     */
    const USERTYPE_ADMINONLY = 1;
    /**
     * @var int
     */
    const USERTYPE_USERONLY = 2;
    /**
     * @var int
     */
    const STATUS_ACTIVE = 1;
    /**
     * @var int
     */
    const STATUS_INACTIVE = 2;
    /**
     * @var int
     */
    const LOGIN_SOME = 1;
    /**
     * @var int
     */
    const LOGIN_NONE = 2;
    /**
     * @var string
     */
    protected $userName = '';

    /**
     * @var int
     */
    protected $userType = self::ALL;

    /**
     * @var int
     */
    protected $status = self::ALL;

    /**
     * @var int
     */
    protected $logins = 0;

    /**
     * @var int
     */
    protected $backendUserGroup = 0;

    /**
     * @param string $userName
     */
    public function setUserName($userName)
    {
        $this->userName = $userName;
    }

    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @param int $userType
     */
    public function setUserType($userType)
    {
        $this->userType = $userType;
    }

    /**
     * @return int
     */
    public function getUserType()
    {
        return $this->userType;
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $logins
     */
    public function setLogins($logins)
    {
        $this->logins = $logins;
    }

    /**
     * @return int
     */
    public function getLogins()
    {
        return $this->logins;
    }

    /**
     * @param int $backendUserGroup
     */
    public function setBackendUserGroup($backendUserGroup)
    {
        $this->backendUserGroup = $backendUserGroup;
    }

    /**
     * @return int
     */
    public function getBackendUserGroup()
    {
        return $this->backendUserGroup;
    }
}
