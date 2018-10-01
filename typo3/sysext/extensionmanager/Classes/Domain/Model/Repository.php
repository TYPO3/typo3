<?php
namespace TYPO3\CMS\Extensionmanager\Domain\Model;

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
 * Repository object for extension manager.
 * @internal This class is a specific domain model implementation and is not part of the Public TYPO3 API.
 */
class Repository extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * Keeps repository title.
     *
     * @var string
     */
    protected $title;

    /**
     * Keeps repository description.
     *
     * @var string
     */
    protected $description;

    /**
     * Keeps mirror list URL.
     *
     * @var string
     */
    protected $mirrorListUrl;

    /**
     * Keeps repository mirrors object.
     *
     * @var \TYPO3\CMS\Extensionmanager\Domain\Model\Mirrors
     */
    protected $mirrors;

    /**
     * Keeps wsdl URL.
     *
     * @var string
     */
    protected $wsdlUrl;

    /**
     * Keeps last update.
     *
     * @var \DateTime
     */
    protected $lastUpdate;

    /**
     * Keeps extension count.
     *
     * @var string
     */
    protected $extensionCount;

    /**
     * Method returns title of a repository.
     *
     * @return string title of repository
     * @see $title, setTitle()
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Method sets title of a repository.
     *
     * @param string $title title of repository to set
     * @see $title, getTitle()
     */
    public function setTitle($title)
    {
        if (!empty($title) && is_string($title)) {
            $this->title = $title;
        }
    }

    /**
     * Method returns description of a repository.
     *
     * @return string title of repository
     * @see $title, setTitle()
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Method sets description of a repository.
     *
     * @param string $description title of repository to set
     */
    public function setDescription($description)
    {
        if (!empty($description) && is_string($description)) {
            $this->description = $description;
        }
    }

    /**
     * Method returns URL of a resource that contains repository mirrors.
     *
     * @return string URL of file that contains repository mirrors
     * @see $mirrorListUrl, getMirrorListUrl()
     */
    public function getMirrorListUrl()
    {
        return $this->mirrorListUrl;
    }

    /**
     * Method sets URL of a resource that contains repository mirrors.
     *
     * Parameter is typically a remote gzipped xml file.
     *
     * @param string $url URL of file that contains repository mirrors
     * @see $mirrorListUrl, getMirrorListUrl()
     */
    public function setMirrorListUrl($url)
    {
        if (empty($url) || !empty($url) && \TYPO3\CMS\Core\Utility\GeneralUtility::isValidUrl($url)) {
            $this->mirrorListUrl = $url;
        }
    }

    /**
     * Method returns URL of repository WSDL.
     *
     * @return string URL of repository WSDL
     * @see $wsdlUrl, setWsdlUrl()
     */
    public function getWsdlUrl()
    {
        return $this->wsdlUrl;
    }

    /**
     * Method sets URL of repository WSDL.
     *
     * @param string $url URL of repository WSDL
     * @see $wsdlUrl, getWsdlUrl()
     */
    public function setWsdlUrl($url)
    {
        if (!empty($url) && \TYPO3\CMS\Core\Utility\GeneralUtility::isValidUrl($url)) {
            $this->wsdlUrl = $url;
        }
    }

    /**
     * Method returns LastUpdate.
     *
     * @return \DateTime timestamp of last update
     */
    public function getLastUpdate()
    {
        return $this->lastUpdate;
    }

    /**
     * Method sets LastUpdate.
     *
     * @param \DateTime $time URL of repository WSDL
     */
    public function setLastUpdate(\DateTime $time)
    {
        $this->lastUpdate = $time;
    }

    /**
     * Method returns extension count
     *
     * @return int count of read extensions
     */
    public function getExtensionCount()
    {
        return $this->extensionCount;
    }

    /**
     * Method sets extension count
     *
     * @param string $count count of read extensions
     */
    public function setExtensionCount($count)
    {
        $this->extensionCount = $count;
    }

    /**
     * Method registers repository mirrors object.
     *
     * Repository mirrors object is passed by reference.
     *
     * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Mirrors $mirrors mirror list
     * @see $mirrors, getMirrors(), hasMirrors(), removeMirrors()
     */
    public function addMirrors(\TYPO3\CMS\Extensionmanager\Domain\Model\Mirrors $mirrors)
    {
        $this->mirrors = $mirrors;
    }

    /**
     * Method returns information if a repository mirrors
     * object has been registered to this repository.
     *
     * @return bool TRUE, if a repository mirrors object has been registered, otherwise FALSE
     * @see $mirrors, addMirrors(), getMirrors(), removeMirrors()
     */
    public function hasMirrors()
    {
        $hasMirrors = false;
        if (is_object($this->mirrors)) {
            $hasMirrors = true;
        }
        return $hasMirrors;
    }

    /**
     * Method returns a repository mirrors object.
     *
     * @return \TYPO3\CMS\Extensionmanager\Domain\Model\Mirrors mirrors for repository
     * @see $mirrors, addMirrors(), hasMirrors(), removeMirrors()
     */
    public function getMirrors()
    {
        return $this->hasMirrors() ? $this->mirrors : null;
    }

    /**
     * Method unregisters a repository mirrors object.
     *
     * @see $mirrors, addMirrors(), getMirrors(), hasMirrors()
     */
    public function removeMirrors()
    {
        unset($this->mirrors);
    }
}
