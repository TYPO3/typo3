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

namespace TYPO3\CMS\Extensionmanager\Utility\Parser;

/**
 * Abstract parser for TYPO3's extension.xml file.
 * @internal This class is a specific ExtensionManager implementation and is not part of the Public TYPO3 API.
 */
abstract class AbstractExtensionXmlParser implements \SplSubject
{
    /**
     * Keeps XML parser instance.
     *
     * @var mixed
     */
    protected $objXml;

    /**
     * Keeps name of required PHP extension
     * for this class to work properly.
     *
     * @var string
     */
    protected $requiredPhpExtensions;

    /**
     * Keeps list of attached observers.
     *
     * @var \SplObserver[]
     */
    protected $observers = [];

    /**
     * Method attaches an observer.
     *
     * @param \SplObserver $observer an observer to attach
     * @see detach()
     * @see notify()
     */
    public function attach(\SplObserver $observer)
    {
        $this->observers[] = $observer;
    }

    /**
     * Method detaches an attached observer
     *
     * @param \SplObserver $observer an observer to detach
     * @see attach()
     * @see notify()
     */
    public function detach(\SplObserver $observer)
    {
        $key = array_search($observer, $this->observers, true);
        if ($key !== false) {
            unset($this->observers[$key]);
        }
    }

    /**
     * Method notifies attached observers.
     *
     * @see attach()
     * @see detach()
     */
    public function notify()
    {
        foreach ($this->observers as $observer) {
            $observer->update($this);
        }
    }

    /**
     * Method determines if a necessary PHP extension is available.
     *
     * Method tries to load the extension if necessary and possible.
     *
     * @return bool TRUE, if PHP extension is available, otherwise FALSE
     */
    public function isAvailable()
    {
        $isAvailable = true;
        if (!extension_loaded($this->requiredPhpExtensions)) {
            $prefix = PHP_SHLIB_SUFFIX === 'dll' ? 'php_' : '';
            if (!(((bool)ini_get('enable_dl') && !(bool)ini_get('safe_mode')) && function_exists('dl') && dl($prefix . $this->requiredPhpExtensions . PHP_SHLIB_SUFFIX))) {
                $isAvailable = false;
            }
        }
        return $isAvailable;
    }

    /**
     * Method parses an XML file.
     *
     * @param string $file GZIP stream resource
     * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException in case of XML parser errors
     */
    abstract public function parseXml($file);

    /**
     * Create required parser
     */
    abstract protected function createParser();

    /**
     * Keeps current author company of an extension's version.
     *
     * @var string
     */
    protected $authorcompany;

    /**
     * Keeps current author mail address of an extension's version.
     *
     * @var string
     */
    protected $authoremail;

    /**
     * Keeps current author name of an extension's version.
     *
     * @var string
     */
    protected $authorname;

    /**
     * Keeps current category of an extension's version.
     *
     * @var string
     */
    protected $category;

    /**
     * Keeps current dependencies of an extension's version.
     *
     * @var string
     */
    protected $dependencies;

    /**
     * Keeps current description of an extension's version.
     *
     * @var string
     */
    protected $description;

    /**
     * Keeps current download number sum of all extension's versions.
     *
     * @var string
     */
    protected $extensionDownloadCounter;

    /**
     * Keeps current key of an extension.
     *
     * @var string
     */
    protected $extensionKey;

    /**
     * Keeps current upload date of an extension's version.
     *
     * @var string
     */
    protected $lastuploaddate;

    /**
     * Keeps current owner username of an extension's version.
     *
     * @var string
     */
    protected $ownerusername;

    /**
     * Keeps current reviewstate of an extension's version.
     *
     * @var string
     */
    protected $reviewstate;

    /**
     * Keeps current state of an extension's version.
     *
     * @var string
     */
    protected $state;

    /**
     * Keeps current t3x file hash of an extension's version.
     *
     * @var string
     */
    protected $t3xfilemd5;

    /**
     * Keeps current title of an extension's version.
     *
     * @var string
     */
    protected $title;

    /**
     * Keeps current upload comment of an extension's version.
     *
     * @var string
     */
    protected $uploadcomment;

    /**
     * Keeps current version number.
     *
     * @var string
     */
    protected $version;

    /**
     * Keeps current download number of an extension's version.
     *
     * @var string
     */
    protected $versionDownloadCounter;

    /**
     * Link to the documentation
     *
     * @var string
     */
    protected $documentationLink;

    /**
     * Returns an associative array of all extension version properties.
     *
     * Valid array keys of returned array are:
     * extkey, version, alldownloadcounter, downloadcounter, title, description,
     * state, reviewstate, category, lastuploaddate, uploadcomment, dependencies,
     * authorname, authoremail, authorcompany, ownerusername, t3xfilemd5
     *
     * @return array associative array of an extension version's properties
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
        $versionProperties['documentationlink'] = $this->documentationLink;
        return $versionProperties;
    }

    /**
     * Returns download number sum of all extension's versions.
     *
     * @return string download number sum
     * @see getAll()
     */
    public function getAlldownloadcounter()
    {
        return $this->extensionDownloadCounter;
    }

    /**
     * Returns company name of extension author.
     *
     * @return string company name of extension author
     * @see getAll()
     */
    public function getAuthorcompany()
    {
        return $this->authorcompany;
    }

    /**
     * Returns e-mail address of extension author.
     *
     * @return string e-mail address of extension author
     * @see getAll()
     */
    public function getAuthoremail()
    {
        return $this->authoremail;
    }

    /**
     * Returns name of extension author.
     *
     * @return string name of extension author
     * @see getAll()
     */
    public function getAuthorname()
    {
        return $this->authorname;
    }

    /**
     * Returns category of an extension.
     *
     * @return string extension category
     * @see getAll()
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Returns dependencies of an extension's version.
     *
     * @return string extension dependencies
     * @see getAll()
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }

    /**
     * Returns description of an extension's version.
     *
     * @return string extension description
     * @see getAll()
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Returns download number of an extension's version.
     *
     * @return string download number
     * @see getAll()
     */
    public function getDownloadcounter()
    {
        return $this->versionDownloadCounter;
    }

    /**
     * Returns key of an extension.
     *
     * @return string extension key
     * @see getAll()
     */
    public function getExtkey()
    {
        return $this->extensionKey;
    }

    /**
     * Returns last uploaddate of an extension's version.
     *
     * @return string last upload date of an extension's version
     * @see getAll()
     */
    public function getLastuploaddate()
    {
        return $this->lastuploaddate;
    }

    /**
     * Returns username of extension owner.
     *
     * @return string extension owner's username
     * @see getAll()
     */
    public function getOwnerusername()
    {
        return $this->ownerusername;
    }

    /**
     * Returns review state of an extension's version.
     *
     * @return string extension review state
     * @see getAll()
     */
    public function getReviewstate()
    {
        return $this->reviewstate;
    }

    /**
     * Returns state of an extension's version.
     *
     * @return string extension state
     * @see getAll()
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Returns t3x file hash of an extension's version.
     *
     * @return string t3x file hash
     * @see getAll()
     */
    public function getT3xfilemd5()
    {
        return $this->t3xfilemd5;
    }

    /**
     * Returns title of an extension's version.
     *
     * @return string extension title
     * @see getAll()
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Returns extension upload comment.
     *
     * @return string extension upload comment
     * @see getAll()
     */
    public function getUploadcomment()
    {
        return $this->uploadcomment;
    }

    /**
     * Returns version number.
     *
     * @return string version number
     * @see getAll()
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getDocumentationLink()
    {
        return $this->documentationLink;
    }

    /**
     * Method resets version class properties.
     *
     * @param bool $resetAll If TRUE, additionally extension properties are reset
     */
    protected function resetProperties($resetAll = false)
    {
        // resetting at least class property "version" is mandatory
        // as we need to do some magic in regards to
        // an extension's and version's child node "downloadcounter"
        $this->version = $this->title = $this->versionDownloadCounter = $this->description = $this->state = $this->reviewstate = $this->category = $this->lastuploaddate = $this->uploadcomment = $this->dependencies = $this->authorname = $this->authoremail = $this->authorcompany = $this->ownerusername = $this->t3xfilemd5 = $this->documentationLink = null;
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
        $dependenciesArray = unserialize($dependencies, ['allowed_classes' => false]);
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
