<?php
namespace TYPO3\CMS\Extensionmanager\Utility\Parser;

/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2013 Marcus Krause <marcus#exp2010@t3sec.info>
 *	 Steffen Kamper <info@sk-typo3.de>
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
 * Module: Extension manager - Extension.xml abstract parser
 *
 * @author Marcus Krause <marcus#exp2010@t3sec.info>
 * @author Steffen Kamper <info@sk-typo3.de>
 */
/**
 * Abstract parser for TYPO3's extension.xml file.
 *
 * @author Marcus Krause <marcus#exp2010@t3sec.info>
 * @author Steffen Kamper <info@sk-typo3.de>
 * @since 2010-02-09
 */
abstract class AbstractExtensionXmlParser extends \TYPO3\CMS\Extensionmanager\Utility\Parser\AbstractXmlParser {

	/**
	 * Keeps current author company of an extension's version.
	 *
	 * @var string
	 */
	protected $authorcompany = NULL;

	/**
	 * Keeps current author mail address of an extension's version.
	 *
	 * @var string
	 */
	protected $authoremail = NULL;

	/**
	 * Keeps current author name of an extension's version.
	 *
	 * @var string
	 */
	protected $authorname = NULL;

	/**
	 * Keeps current category of an extension's version.
	 *
	 * @var string
	 */
	protected $category = NULL;

	/**
	 * Keeps current dependencies of an extension's version.
	 *
	 * @var string
	 */
	protected $dependencies = NULL;

	/**
	 * Keeps current description of an extension's version.
	 *
	 * @var string
	 */
	protected $description = NULL;

	/**
	 * Keeps current download number sum of all extension's versions.
	 *
	 * @var string
	 */
	protected $extensionDownloadCounter = NULL;

	/**
	 * Keeps current key of an extension.
	 *
	 * @var string
	 */
	protected $extensionKey = NULL;

	/**
	 * Keeps current upload date of an extension's version.
	 *
	 * @var string
	 */
	protected $lastuploaddate = NULL;

	/**
	 * Keeps current owner username of an extension's version.
	 *
	 * @var string
	 */
	protected $ownerusername = NULL;

	/**
	 * Keeps current reviewstate of an extension's version.
	 *
	 * @var string
	 */
	protected $reviewstate = NULL;

	/**
	 * Keeps current state of an extension's version.
	 *
	 * @var string
	 */
	protected $state = NULL;

	/**
	 * Keeps current t3x file hash of an extension's version.
	 *
	 * @var string
	 */
	protected $t3xfilemd5 = NULL;

	/**
	 * Keeps current title of an extension's version.
	 *
	 * @var string
	 */
	protected $title = NULL;

	/**
	 * Keeps current upload comment of an extension's version.
	 *
	 * @var string
	 */
	protected $uploadcomment = NULL;

	/**
	 * Keeps current version number.
	 *
	 * @var string
	 */
	protected $version = NULL;

	/**
	 * Keeps current download number of an extension's version.
	 *
	 * @var string
	 */
	protected $versionDownloadCounter = NULL;

	/**
	 * Returns an assoziative array of all extension version properties.
	 *
	 * Valid array keys of returned array are:
	 * extkey, version, alldownloadcounter, downloadcounter, title, description,
	 * state, reviewstate, category, lastuploaddate, uploadcomment, dependencies,
	 * authorname, authoremail, authorcompany, ownerusername, t3xfilemd5
	 *
	 * @access public
	 * @see $extensionKey, $version, $extensionDownloadCounter,
	 * @return array assoziative array of an extension version's properties
	 */
	public function getAll() {
		$versionProperties = array();
		$versionProperties['extkey'] = $this->extensionKey;
		$versionProperties['version'] = $this->version;
		$versionProperties['alldownloadcounter'] = $this->extensionDownloadCounter;
		$versionProperties['downloadcounter'] = $this->versionDownloadCounter;
		$versionProperties['title'] = $this->title;
		$versionProperties['description'] = $this->description;
		$versionProperties['state'] = $this->state;
		$versionProperties['reviewstate'] = $this->reviewstate;
		$versionProperties['category'] = $this->category;
		$versionProperties['lastuploaddate'] = $this->lastuploaddate;
		$versionProperties['uploadcomment'] = $this->uploadcomment;
		$versionProperties['dependencies'] = $this->dependencies;
		$versionProperties['authorname'] = $this->authorname;
		$versionProperties['authoremail'] = $this->authoremail;
		$versionProperties['authorcompany'] = $this->authorcompany;
		$versionProperties['ownerusername'] = $this->ownerusername;
		$versionProperties['t3xfilemd5'] = $this->t3xfilemd5;
		return $versionProperties;
	}

	/**
	 * Returns download number sum of all extension's versions.
	 *
	 * @access public
	 * @return string download number sum
	 * @see $extensionDLCounter, getAll()
	 */
	public function getAlldownloadcounter() {
		return $this->extensionDownloadCounter;
	}

	/**
	 * Returns company name of extension author.
	 *
	 * @access public
	 * @return string company name of extension author
	 * @see $authorcompany, getAll()
	 */
	public function getAuthorcompany() {
		return $this->authorcompany;
	}

	/**
	 * Returns e-mail address of extension author.
	 *
	 * @access public
	 * @return string e-mail address of extension author
	 * @see 	 $authoremail, getAll()
	 */
	public function getAuthoremail() {
		return $this->authoremail;
	}

	/**
	 * Returns name of extension author.
	 *
	 * @access public
	 * @return string name of extension author
	 * @see $authorname, getAll()
	 */
	public function getAuthorname() {
		return $this->authorname;
	}

	/**
	 * Returns category of an extension.
	 *
	 * @access public
	 * @return string extension category
	 * @see $category, getAll()
	 */
	public function getCategory() {
		return $this->category;
	}

	/**
	 * Returns dependencies of an extension's version.
	 *
	 * @access public
	 * @return string extension dependencies
	 * @see $dependencies, getAll()
	 */
	public function getDependencies() {
		return $this->dependencies;
	}

	/**
	 * Returns description of an extension's version.
	 *
	 * @access public
	 * @return string extension description
	 * @see $description, getAll()
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * Returns download number of an extension's version.
	 *
	 * @access public
	 * @return string download number
	 * @see $versionDLCounter, getAll()
	 */
	public function getDownloadcounter() {
		return $this->versionDownloadCounter;
	}

	/**
	 * Returns key of an extension.
	 *
	 * @access public
	 * @return string extension key
	 * @see $extensionKey, getAll()
	 */
	public function getExtkey() {
		return $this->extensionKey;
	}

	/**
	 * Returns last uploaddate of an extension's version.
	 *
	 * @access public
	 * @return string last upload date of an extension's version
	 * @see $lastuploaddate, getAll()
	 */
	public function getLastuploaddate() {
		return $this->lastuploaddate;
	}

	/**
	 * Returns username of extension owner.
	 *
	 * @access public
	 * @return string extension owner's username
	 * @see $ownerusername, getAll()
	 */
	public function getOwnerusername() {
		return $this->ownerusername;
	}

	/**
	 * Returns review state of an extension's version.
	 *
	 * @access public
	 * @return string extension review state
	 * @see $reviewstate, getAll()
	 */
	public function getReviewstate() {
		return $this->reviewstate;
	}

	/**
	 * Returns state of an extension's version.
	 *
	 * @access public
	 * @return string extension state
	 * @see $state, getAll()
	 */
	public function getState() {
		return $this->state;
	}

	/**
	 * Returns t3x file hash of an extension's version.
	 *
	 * @access public
	 * @return string t3x file hash	 *
	 * @see $t3xfilemd5, getAll()
	 */
	public function getT3xfilemd5() {
		return $this->t3xfilemd5;
	}

	/**
	 * Returns title of an extension's version.
	 *
	 * @access public
	 * @return string extension title
	 * @see $title, getAll()
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Returns extension upload comment.
	 *
	 * @access public
	 * @return string extension upload comment
	 * @see $uploadcomment, getAll()
	 */
	public function getUploadcomment() {
		return $this->uploadcomment;
	}

	/**
	 * Returns version number.
	 *
	 * @access public
	 * @return string version number
	 * @see $version, getAll()
	 */
	public function getVersion() {
		return $this->version;
	}

	/**
	 * Method resets version class properties.
	 *
	 * @param $resetAll $all if TRUE, additionally extension properties are reset
	 * @return void
	 * @see $extensionKey, $version, $extensionDLCounter, $versionDLCounter,
	 */
	protected function resetProperties($resetAll = FALSE) {
		// resetting at least class property "version" is mandatory
		// as we need to do some magic in regards to
		// an extension's and version's child node "downloadcounter"
		$this->version = ($this->title = ($this->versionDownloadCounter = ($this->description = ($this->state = ($this->reviewstate = ($this->category = ($this->lastuploaddate = ($this->uploadcomment = ($this->dependencies = ($this->authorname = ($this->authoremail = ($this->authorcompany = ($this->ownerusername = ($this->t3xfilemd5 = NULL))))))))))))));
		if ($resetAll) {
			$this->extensionKey = ($this->extensionDownloadCounter = NULL);
		}
	}

	/**
	 * Convert dependencies from TER format to EM_CONF format
	 *
	 * @param string $dependencies serialized dependency array
	 * @return string
	 */
	protected function convertDependencies($dependencies) {
		$newDependencies = array();
		$dependenciesArray = unserialize($dependencies);
		if (is_array($dependenciesArray)) {
			foreach ($dependenciesArray as $version) {
				$newDependencies[$version['kind']][$version['extensionKey']] = $version['versionRange'];
			}
		}
		return serialize($newDependencies);
	}

}


?>