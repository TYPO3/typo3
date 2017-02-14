<?php
namespace TYPO3\CMS\Extensionmanager\Utility\Parser;

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
 * Abstract parser for TYPO3's extension.xml file.
 */
abstract class AbstractExtensionXmlParser extends AbstractXmlParser
{
    /**
     * Keeps current author company of an extension's version.
     *
     * @var string
     */
    protected $authorcompany = null;

    /**
     * Keeps current author mail address of an extension's version.
     *
     * @var string
     */
    protected $authoremail = null;

    /**
     * Keeps current author name of an extension's version.
     *
     * @var string
     */
    protected $authorname = null;

    /**
     * Keeps current category of an extension's version.
     *
     * @var string
     */
    protected $category = null;

    /**
     * Keeps current dependencies of an extension's version.
     *
     * @var string
     */
    protected $dependencies = null;

    /**
     * Keeps current description of an extension's version.
     *
     * @var string
     */
    protected $description = null;

    /**
     * Keeps current download number sum of all extension's versions.
     *
     * @var string
     */
    protected $extensionDownloadCounter = null;

    /**
     * Keeps current key of an extension.
     *
     * @var string
     */
    protected $extensionKey = null;

    /**
     * Keeps current upload date of an extension's version.
     *
     * @var string
     */
    protected $lastuploaddate = null;

    /**
     * Keeps current owner username of an extension's version.
     *
     * @var string
     */
    protected $ownerusername = null;

    /**
     * Keeps current reviewstate of an extension's version.
     *
     * @var string
     */
    protected $reviewstate = null;

    /**
     * Keeps current state of an extension's version.
     *
     * @var string
     */
    protected $state = null;

    /**
     * Keeps current t3x file hash of an extension's version.
     *
     * @var string
     */
    protected $t3xfilemd5 = null;

    /**
     * Keeps current title of an extension's version.
     *
     * @var string
     */
    protected $title = null;

    /**
     * Keeps current upload comment of an extension's version.
     *
     * @var string
     */
    protected $uploadcomment = null;

    /**
     * Keeps current version number.
     *
     * @var string
     */
    protected $version = null;

    /**
     * Keeps current download number of an extension's version.
     *
     * @var string
     */
    protected $versionDownloadCounter = null;

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
    public function getAll()
    {
        $versionProperties = [];
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
    public function getAlldownloadcounter()
    {
        return $this->extensionDownloadCounter;
    }

    /**
     * Returns company name of extension author.
     *
     * @access public
     * @return string company name of extension author
     * @see $authorcompany, getAll()
     */
    public function getAuthorcompany()
    {
        return $this->authorcompany;
    }

    /**
     * Returns e-mail address of extension author.
     *
     * @access public
     * @return string e-mail address of extension author
     * @see $authoremail, getAll()
     */
    public function getAuthoremail()
    {
        return $this->authoremail;
    }

    /**
     * Returns name of extension author.
     *
     * @access public
     * @return string name of extension author
     * @see $authorname, getAll()
     */
    public function getAuthorname()
    {
        return $this->authorname;
    }

    /**
     * Returns category of an extension.
     *
     * @access public
     * @return string extension category
     * @see $category, getAll()
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Returns dependencies of an extension's version.
     *
     * @access public
     * @return string extension dependencies
     * @see $dependencies, getAll()
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }

    /**
     * Returns description of an extension's version.
     *
     * @access public
     * @return string extension description
     * @see $description, getAll()
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Returns download number of an extension's version.
     *
     * @access public
     * @return string download number
     * @see $versionDLCounter, getAll()
     */
    public function getDownloadcounter()
    {
        return $this->versionDownloadCounter;
    }

    /**
     * Returns key of an extension.
     *
     * @access public
     * @return string extension key
     * @see $extensionKey, getAll()
     */
    public function getExtkey()
    {
        return $this->extensionKey;
    }

    /**
     * Returns last uploaddate of an extension's version.
     *
     * @access public
     * @return string last upload date of an extension's version
     * @see $lastuploaddate, getAll()
     */
    public function getLastuploaddate()
    {
        return $this->lastuploaddate;
    }

    /**
     * Returns username of extension owner.
     *
     * @access public
     * @return string extension owner's username
     * @see $ownerusername, getAll()
     */
    public function getOwnerusername()
    {
        return $this->ownerusername;
    }

    /**
     * Returns review state of an extension's version.
     *
     * @access public
     * @return string extension review state
     * @see $reviewstate, getAll()
     */
    public function getReviewstate()
    {
        return $this->reviewstate;
    }

    /**
     * Returns state of an extension's version.
     *
     * @access public
     * @return string extension state
     * @see $state, getAll()
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Returns t3x file hash of an extension's version.
     *
     * @access public
     * @return string t3x file hash	 *
     * @see $t3xfilemd5, getAll()
     */
    public function getT3xfilemd5()
    {
        return $this->t3xfilemd5;
    }

    /**
     * Returns title of an extension's version.
     *
     * @access public
     * @return string extension title
     * @see $title, getAll()
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Returns extension upload comment.
     *
     * @access public
     * @return string extension upload comment
     * @see $uploadcomment, getAll()
     */
    public function getUploadcomment()
    {
        return $this->uploadcomment;
    }

    /**
     * Returns version number.
     *
     * @access public
     * @return string version number
     * @see $version, getAll()
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Method resets version class properties.
     *
     * @param bool $resetAll If TRUE, additionally extension properties are reset
     * @see $extensionKey, $version, $extensionDLCounter, $versionDLCounter,
     */
    protected function resetProperties($resetAll = false)
    {
        // resetting at least class property "version" is mandatory
        // as we need to do some magic in regards to
        // an extension's and version's child node "downloadcounter"
        $this->version = $this->title = $this->versionDownloadCounter = $this->description = $this->state = $this->reviewstate = $this->category = $this->lastuploaddate = $this->uploadcomment = $this->dependencies = $this->authorname = $this->authoremail = $this->authorcompany = $this->ownerusername = $this->t3xfilemd5 = null;
        if ($resetAll) {
            $this->extensionKey = $this->extensionDownloadCounter = null;
        }
    }

    /**
     * Convert dependencies from TER format to EM_CONF format
     *
     * @param string $dependencies serialized dependency array
     * @return string
     */
    protected function convertDependencies($dependencies)
    {
        $newDependencies = [];
        $dependenciesArray = unserialize($dependencies);
        if (is_array($dependenciesArray)) {
            foreach ($dependenciesArray as $version) {
                if (!empty($version['kind']) && !empty($version['extensionKey'])) {
                    $newDependencies[$version['kind']][$version['extensionKey']] = $version['versionRange'];
                }
            }
        }
        return serialize($newDependencies);
    }
}
