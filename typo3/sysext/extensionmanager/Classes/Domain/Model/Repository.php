<?php
namespace TYPO3\CMS\Extensionmanager\Domain\Model;

/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2013 Marcus Krause <marcus#exp2010@t3sec.info>
 *          Steffen Kamper <info@sk-typo3.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Repository object for extension manager.
 *
 * @author Marcus Krause <marcus#exp2010@t3sec.info>
 * @author Steffen Kamper <info@sk-typo3.de>
 * @since 2010-02-11
 */
class Repository extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

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
	 * @access public
	 * @return string title of repository
	 * @see $title, setTitle()
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Method sets title of a repository.
	 *
	 * @access public
	 * @param string $title title of repository to set
	 * @return void
	 * @see $title, getTitle()
	 */
	public function setTitle($title) {
		if (!empty($title) && is_string($title)) {
			$this->title = $title;
		}
	}

	/**
	 * Method returns description of a repository.
	 *
	 * @access public
	 * @return string title of repository
	 * @see $title, setTitle()
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * Method sets description of a repository.
	 *
	 * @access public
	 * @param string $description title of repository to set
	 * @return void
	 */
	public function setDescription($description) {
		if (!empty($description) && is_string($description)) {
			$this->description = $description;
		}
	}

	/**
	 * Method returns URL of a resource that contains repository mirrors.
	 *
	 * @access public
	 * @return string URL of file that contains repository mirros
	 * @see $mirrorListUrl, getMirrorListUrl()
	 */
	public function getMirrorListUrl() {
		return $this->mirrorListUrl;
	}

	/**
	 * Method sets URL of a resource that contains repository mirrors.
	 *
	 * Parameter is typically a remote gzipped xml file.
	 *
	 * @access public
	 * @param string $url URL of file that contains repository mirrors
	 * @return void
	 * @see $mirrorListUrl, getMirrorListUrl()
	 */
	public function setMirrorListUrl($url) {
		if (empty($url) || !empty($url) && \TYPO3\CMS\Core\Utility\GeneralUtility::isValidUrl($url)) {
			$this->mirrorListUrl = $url;
		}
	}

	/**
	 * Method returns URL of repository WSDL.
	 *
	 * @access public
	 * @return string URL of repository WSDL
	 * @see $wsdlUrl, setWsdlUrl()
	 */
	public function getWsdlUrl() {
		return $this->wsdlUrl;
	}

	/**
	 * Method sets URL of repository WSDL.
	 *
	 * @param string $url URL of repository WSDL
	 * @return void
	 * @see $wsdlUrl, getWsdlUrl()
	 */
	public function setWsdlUrl($url) {
		if (!empty($url) && \TYPO3\CMS\Core\Utility\GeneralUtility::isValidUrl($url)) {
			$this->wsdlUrl = $url;
		}
	}

	/**
	 * Method returns LastUpdate.
	 *
	 * @access public
	 * @return \DateTime timestamp of last update
	 */
	public function getLastUpdate() {
		return $this->lastUpdate;
	}

	/**
	 * Method sets LastUpdate.
	 *
	 * @access public
	 * @param \DateTime $time URL of repository WSDL
	 * @return void
	 */
	public function setLastUpdate(\DateTime $time) {
		$this->lastUpdate = $time;
	}

	/**
	 * Method returns extension count
	 *
	 * @access public
	 * @return integer count of read extensions
	 */
	public function getExtensionCount() {
		return $this->extensionCount;
	}

	/**
	 * Method sets extension count
	 *
	 * @access public
	 * @param string $count count of read extensions
	 * @return void
	 */
	public function setExtensionCount($count) {
		$this->extensionCount = $count;
	}

	/**
	 * Method registers repository mirrors object.
	 *
	 * Repository mirrors object is passed by reference.
	 *
	 * @access public
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Mirrors $mirrors mirror list
	 * @return void
	 * @see $mirrors, getMirrors(), hasMirrors(), removeMirrors()
	 */
	public function addMirrors(\TYPO3\CMS\Extensionmanager\Domain\Model\Mirrors $mirrors) {
		$this->mirrors = $mirrors;
	}

	/**
	 * Method returns information if a repository mirrors
	 * object has been registered to this repository.
	 *
	 * @access public
	 * @return boolean TRUE, if a repository mirrors object has been registered, otherwise FALSE
	 * @see $mirrors, addMirrors(), getMirrors(), removeMirrors()
	 */
	public function hasMirrors() {
		$hasMirrors = FALSE;
		if (is_object($this->mirrors)) {
			$hasMirrors = TRUE;
		}
		return $hasMirrors;
	}

	/**
	 * Method returns a repository mirrors object.
	 *
	 * @access public
	 * @return \TYPO3\CMS\Extensionmanager\Domain\Model\Mirrors mirrors for repository
	 * @see $mirrors, addMirrors(), hasMirrors(), removeMirrors()
	 */
	public function getMirrors() {
		return $this->hasMirrors() ? $this->mirrors : NULL;
	}

	/**
	 * Method unregisters a repository mirrors object.
	 *
	 * @access public
	 * @return void
	 * @see $mirrors, addMirrors(), getMirrors(), hasMirrors()
	 */
	public function removeMirrors() {
		unset($this->mirrors);
	}

}


?>