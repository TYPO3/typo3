<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Marcus Krause <marcus#exp2010@t3sec.info>
 *		   Steffen Kamper <info@sk-typo3.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * class.tx_em_repository.php
 *
 * Module: Extension manager - Repository
 *
 * @author  Marcus Krause <marcus#exp2010@t3sec.info>
 * @author  Steffen Kamper <info@sk-typo3.de>
 */


/**
 * Repository object for extension manager.
 *
 * @author	  Marcus Krause <marcus#exp2010@t3sec.info>
 * @author	  Steffen Kamper <info@sk-typo3.de>
 *
 * @since	   2010-02-11
 * @package	 TYPO3
 * @subpackage  EM
 */
class tx_em_Repository {

	/**
	 * Keeps repository identifier.
	 *
	 * @var  string
	 */
	protected $id;

	/**
	 * Keeps repository title.
	 *
	 * @var  string
	 */
	protected $title;

	/**
	 * Keeps repository description.
	 *
	 * @var  string
	 */
	protected $description;

	/**
	 * Keeps repository priority.
	 *
	 * @var  integer
	 */
	protected $priority;

	/**
	 * Keeps mirror list URL.
	 *
	 * @var  string
	 */
	protected $mirrorListUrl;

	/**
	 * Keeps repository mirrors object.
	 *
	 * @var  tx_em_Repository_Mirrors
	 */
	protected $mirrors;

	/**
	 * Keeps wsdl URL.
	 *
	 * @var  string
	 */
	protected $wsdlUrl;

	/**
	 * Keeps last update.
	 *
	 * @var  string
	 */
	protected $lastUpdate;

	/**
	 * Keeps extension count.
	 *
	 * @var  string
	 */
	protected $extensionCount;


	/**
	 * Class constructor.
	 *
	 * Initializes repository with properties of TYPO3.org main repository.
	 *
	 * @access  public
	 * @return  void
	 */
	function __construct($uid = 1) {
		$row = tx_em_Database::getRepositoryByUID($uid);
		if (!is_array($row) && $uid === 1) {
			$this->fixMainRepository();
		} else {
			$this->setTitle($row['title']);
			$this->setDescription($row['description']);
			$this->setId($row['uid']);
			$this->setPriority(1);
			$this->setMirrorListUrl($row['mirror_url']);
			$this->setWsdlUrl($row['wsdl_url']);
			$this->setLastUpdate($row['lastUpdated']);
			$this->setExtensionCount($row['extCount']);
		}
	}

	/**
	 * Method returns uid of a repository.
	 *
	 * @access  public
	 * @return  int  ID of a repository
	 * @see	 $id, setId()
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Method sets uid of a repository.
	 *
	 * @access  public
	 * @param   string  $id  ID of repository to set
	 * @return  void
	 * @see	 $id, getId()
	 */
	public function setId($id) {
		$this->id = intval($id);
	}

	/**
	 * Method returns title of a repository.
	 *
	 * @access  public
	 * @return  string  title of repository
	 * @see	 $title, setTitle()
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Method sets title of a repository.
	 *
	 * @access  public
	 * @param   string  $title  title of repository to set
	 * @return  void
	 * @see	 $title, getTitle()
	 */
	public function setTitle($title) {
		if (!empty($title) && is_string($title)) {
			$this->title = $title;
		}
	}

	/**
	 * Method returns description of a repository.
	 *
	 * @access  public
	 * @return  string  title of repository
	 * @see	 $title, setTitle()
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * Method sets description of a repository.
	 *
	 * @access  public
	 * @param   string  $description  title of repository to set
	 * @return  void
	 */
	public function setDescription($description) {
		if (!empty($description) && is_string($description)) {
			$this->description = $description;
		}
	}

	/**
	 * Method returns priority of a repository.
	 *
	 * The repository with lowest priority value takes precedence over
	 * those that have a higher value.
	 *
	 * @access  public
	 * @return  integer
	 * @see	 $priority, setPriority()
	 * @todo	repository priority is currently unused
	 */
	public function getPriority() {
		return $this->priority;
	}

	/**
	 * Method sets priority of a repository.
	 *
	 * The repository with lowest priority value takes precedence over
	 * those that have a higher value.
	 *
	 * @access  public
	 * @param   integer  $priority  priority value to set
	 * @return  void
	 * @see	 $priority, getPriority()
	 * @todo	repository priority is currently unused
	 */
	public function setPriority($priority) {
		if (!empty($priority) && is_int($priority)) {
			$this->priority = $priority;
		}
	}

	/**
	 * Method returns URL of a resource that contains repository mirrors.
	 *
	 * @access  public
	 * @return  string  URL of file that contains repository mirros
	 * @see	 $mirrorListUrl, getMirrorListUrl()
	 */
	public function getMirrorListUrl() {
		return $this->mirrorListUrl;
	}

	/**
	 * Method sets URL of a resource that contains repository mirrors.
	 *
	 * Parameter is typically a remote gzipped xml file.
	 *
	 * @access  public
	 * @param   string  $url  URL of file that contains repository mirrors
	 * @return  void
	 * @see	 $mirrorListUrl, getMirrorListUrl()
	 */
	public function setMirrorListUrl($url) {
		if (empty($url) || (!empty($url) && t3lib_div::isValidUrl($url))) {
			$this->mirrorListUrl = $url;
		}
	}

	/**
	 * Method returns URL of repository WSDL.
	 *
	 * @access  public
	 * @return  string  URL of repository WSDL
	 * @see	 $wsdlUrl, setWsdlUrl()
	 */
	public function getWsdlUrl() {
		return $this->wsdlUrl;
	}

	/**
	 * Method sets URL of repository WSDL.
	 *
	 * @access  public
	 * @param   string  $url  URL of repository WSDL
	 * @return  void
	 * @see	 $wsdlUrl, getWsdlUrl()
	 */
	public function setWsdlUrl($url) {
		if (!empty($url) && t3lib_div::isValidUrl($url)) {
			$this->wsdlUrl = $url;
		}
	}

	/**
	 * Method returns LastUpdate.
	 *
	 * @access  public
	 * @return  int  timestamp of last update
	 */
	public function getLastUpdate() {
		return $this->lastUpdate;
	}

	/**
	 * Method sets LastUpdate.
	 *
	 * @access  public
	 * @param   int  $time  URL of repository WSDL
	 * @return  void
	 */
	public function setLastUpdate($time) {
		$this->lastUpdate = $time;
	}

	/**
	 * Method returns extension count
	 *
	 * @access  public
	 * @return  int count of read extensions
	 */
	public function getExtensionCount() {
		return $this->extensionCount;
	}

	/**
	 * Method sets extension count
	 *
	 * @access  public
	 * @param   string  $count count of read extensions
	 * @return  void
	 */
	public function setExtensionCount($count) {
		$this->extensionCount = $count;
	}

	/**
	 * Method registers repository mirrors object.
	 *
	 * Repository mirrors object is passed by reference.
	 *
	 * @access  public
	 * @param   tx_em_Repository_Mirrors  $mirrors  instance of {@link tx_em_Repository_Mirrors repository mirrors} class
	 * @return  void
	 * @see	 $mirrors, getMirrors(), hasMirrors(), removeMirrors()
	 */
	public function addMirrors(tx_em_Repository_Mirrors $mirrors) {
		$this->mirrors = $mirrors;
	}

	/**
	 * Method returns information if a repository mirrors
	 * object has been registered to this repository.
	 *
	 * @access  public
	 * @return  boolean  TRUE, if a repository mirrors object has been registered, otherwise FALSE
	 * @see	 $mirrors, addMirrors(), getMirrors(), removeMirrors()
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
	 * @access  public
	 * @return  tx_em_Repository_Mirrors  registered instance of {@link tx_em_Repository_Mirrors repository mirrors} class or NULL
	 * @see	 $mirrors, addMirrors(), hasMirrors(), removeMirrors()
	 */
	public function getMirrors() {
		return $this->hasMirrors() ? $this->mirrors : NULL;
	}

	/**
	 * Method unregisters a repository mirrors object.
	 *
	 * @access  public
	 * @return  void
	 * @see	 $mirrors, addMirrors(), getMirrors(), hasMirrors()
	 */
	public function removeMirrors() {
		unset($this->mirrors);
	}

	/**
	 * Insert main repository if not present
	 *
	 * @return void
	 */
	protected function fixMainRepository() {
		$this->setTitle('TYPO3.org Main Repository');
		$this->setId('1');
		$this->setPriority(1);
		$this->setDescription('Main repository on typo3.org. For extension download there are mirrors available.');
		$this->setMirrorListUrl('http://repositories.typo3.org/mirrors.xml.gz');
		$this->setWsdlUrl('http://typo3.org/wsdl/tx_ter_wsdl.php');
		tx_em_Database::insertRepository($this);
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/repository/class.tx_em_repository.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/repository/class.tx_em_repository.php']);
}

?>