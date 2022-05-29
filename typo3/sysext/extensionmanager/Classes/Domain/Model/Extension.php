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

namespace TYPO3\CMS\Extensionmanager\Domain\Model;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Main extension model
 * @internal This class is a specific domain model implementation and is not part of the Public TYPO3 API.
 */
class Extension extends AbstractEntity
{
    /**
     * Category index for distributions
     */
    const DISTRIBUTION_CATEGORY = 10;

    /**
     * Contains default categories.
     *
     * @var array
     */
    protected static $defaultCategories = [
        0 => 'be',
        1 => 'module',
        2 => 'fe',
        3 => 'plugin',
        4 => 'misc',
        5 => 'services',
        6 => 'templates',
        8 => 'doc',
        9 => 'example',
        self::DISTRIBUTION_CATEGORY => 'distribution',
    ];

    /**
     * Contains default states.
     *
     * @var array
     */
    protected static $defaultStates = [
        0 => 'alpha',
        1 => 'beta',
        2 => 'stable',
        3 => 'experimental',
        4 => 'test',
        5 => 'obsolete',
        6 => 'excludeFromUpdates',
        7 => 'deprecated',
        999 => 'n/a',
    ];

    /**
     * @var string
     */
    protected $extensionKey = '';

    /**
     * @var string
     */
    protected $version = '';

    /**
     * @var int
     */
    protected $integerVersion = 0;

    /**
     * @var string
     */
    protected $title = '';

    /**
     * @var string
     */
    protected $description = '';

    /**
     * @var int
     */
    protected $state = 0;

    /**
     * @var int
     */
    protected $category = 0;

    /**
     * @var \DateTime
     */
    protected $lastUpdated;

    /**
     * @var string
     */
    protected $updateComment = '';

    /**
     * @var string
     */
    protected $authorName = '';

    /**
     * @var string
     */
    protected $authorEmail = '';

    /**
     * @var bool
     */
    protected $currentVersion = false;

    /**
     * @var string
     */
    protected $md5hash = '';

    /**
     * @var int
     */
    protected $reviewState;

    /**
     * @var int
     */
    protected $alldownloadcounter;

    /**
     * @var string
     */
    protected $serializedDependencies = '';

    /**
     * @var \SplObjectStorage<Dependency>
     */
    protected $dependencies;

    /**
     * @var string
     */
    protected $documentationLink = '';

    /**
     * @var string
     */
    protected $distributionImage = '';

    /**
     * @var string
     */
    protected $distributionWelcomeImage = '';

    /**
     * @var string
     */
    protected $remote;

    /**
     * @internal
     * @var int
     */
    protected $position = 0;

    /**
     * @param string $authorEmail
     */
    public function setAuthorEmail($authorEmail)
    {
        $this->authorEmail = $authorEmail;
    }

    /**
     * @return string
     */
    public function getAuthorEmail()
    {
        return $this->authorEmail;
    }

    /**
     * @param string $authorName
     */
    public function setAuthorName($authorName)
    {
        $this->authorName = $authorName;
    }

    /**
     * @return string
     */
    public function getAuthorName()
    {
        return $this->authorName;
    }

    /**
     * @param int $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * @return int
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Get Category String
     *
     * @return string
     */
    public function getCategoryString()
    {
        $categoryString = '';
        if (isset(self::$defaultCategories[$this->getCategory()])) {
            $categoryString = self::$defaultCategories[$this->getCategory()];
        }
        return $categoryString;
    }

    /**
     * Returns category index from a given string or an integer.
     * Fallback to 4 - 'misc' in case string is not found or integer ist out of range.
     *
     * @param string|int $category Category string or integer
     * @return int Valid category index
     */
    public function getCategoryIndexFromStringOrNumber($category)
    {
        $categoryIndex = 4;
        if (MathUtility::canBeInterpretedAsInteger($category)) {
            $categoryIndex = (int)$category;
            if ($categoryIndex < 0 || $categoryIndex > 10) {
                $categoryIndex = 4;
            }
        } elseif (is_string($category)) {
            $categoryIndex = array_search($category, self::$defaultCategories);
            if ($categoryIndex === false) {
                $categoryIndex = 4;
            }
        }
        return (int)$categoryIndex;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $extensionKey
     */
    public function setExtensionKey($extensionKey)
    {
        $this->extensionKey = $extensionKey;
    }

    /**
     * @return string
     */
    public function getExtensionKey()
    {
        return $this->extensionKey;
    }

    /**
     * @param \DateTime $lastUpdated
     */
    public function setLastUpdated(\DateTime $lastUpdated)
    {
        $this->lastUpdated = $lastUpdated;
    }

    /**
     * @return \DateTime
     */
    public function getLastUpdated()
    {
        return $this->lastUpdated;
    }

    /**
     * @param int $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Get State string
     *
     * @return string
     */
    public function getStateString()
    {
        $stateString = '';
        if (isset(self::$defaultStates[$this->getState()])) {
            $stateString = self::$defaultStates[$this->getState()];
        }
        return $stateString;
    }

    /**
     * Returns either array with all default states or index/title
     * of a state entry.
     *
     * @param mixed $state state title or state index
     * @return mixed
     */
    public function getDefaultState($state = null)
    {
        $defaultState = '';
        if ($state === null) {
            $defaultState = self::$defaultStates;
        } else {
            if (is_string($state)) {
                $stateIndex = array_search(strtolower($state), self::$defaultStates);
                if ($stateIndex === false) {
                    // default state
                    $stateIndex = 999;
                }
                $defaultState = $stateIndex;
            } else {
                if (is_int($state) && $state >= 0) {
                    if (array_key_exists($state, self::$defaultStates)) {
                        $stateTitle = self::$defaultStates[$state];
                    } else {
                        // default state
                        $stateTitle = 'n/a';
                    }
                    $defaultState = $stateTitle;
                }
            }
        }
        return $defaultState;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $updateComment
     */
    public function setUpdateComment($updateComment)
    {
        $this->updateComment = $updateComment;
    }

    /**
     * @return string
     */
    public function getUpdateComment()
    {
        return $this->updateComment;
    }

    /**
     * @param string $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param bool $currentVersion
     */
    public function setCurrentVersion($currentVersion)
    {
        $this->currentVersion = $currentVersion;
    }

    /**
     * @return bool
     */
    public function getCurrentVersion()
    {
        return $this->currentVersion;
    }

    /**
     * @param string $md5hash
     */
    public function setMd5hash($md5hash)
    {
        $this->md5hash = $md5hash;
    }

    /**
     * @return string
     */
    public function getMd5hash()
    {
        return $this->md5hash;
    }

    /**
     * Possible install paths
     *
     * @static
     * @return array
     */
    public static function returnInstallPaths()
    {
        $installPaths = [
            'System' => Environment::getFrameworkBasePath() . '/',
            'Global' => Environment::getBackendPath() . '/ext/',
            'Local' => Environment::getExtensionsPath() . '/',
        ];
        return $installPaths;
    }

    /**
     * Allowed install paths
     *
     * @static
     * @return array
     */
    public static function returnAllowedInstallPaths()
    {
        $installPaths = self::returnInstallPaths();
        if (empty($GLOBALS['TYPO3_CONF_VARS']['EXT']['allowGlobalInstall'])) {
            unset($installPaths['Global']);
        }
        if (empty($GLOBALS['TYPO3_CONF_VARS']['EXT']['allowLocalInstall'])) {
            unset($installPaths['Local']);
        }
        return $installPaths;
    }

    /**
     * Allowed install names: System, Global, Local
     *
     * @static
     * @return array
     */
    public static function returnAllowedInstallTypes()
    {
        $installPaths = self::returnAllowedInstallPaths();
        return array_keys($installPaths);
    }

    /**
     * @param string $dependencies
     */
    public function setSerializedDependencies($dependencies)
    {
        $this->serializedDependencies = $dependencies;
    }

    /**
     * @return string
     */
    public function getSerializedDependencies()
    {
        return $this->serializedDependencies;
    }

    /**
     * @param \SplObjectStorage<Dependency> $dependencies
     */
    public function setDependencies($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @return \SplObjectStorage<Dependency>
     */
    public function getDependencies()
    {
        if (!is_object($this->dependencies)) {
            $this->setDependencies($this->convertDependenciesToObjects($this->getSerializedDependencies()));
        }
        return $this->dependencies;
    }

    public function getTypo3Dependency(): ?Dependency
    {
        foreach ($this->getDependencies() as $dependency) {
            if ($dependency->getIdentifier() === 'typo3') {
                return $dependency;
            }
        }
        return null;
    }

    /**
     * @param Dependency $dependency
     */
    public function addDependency(Dependency $dependency)
    {
        $this->dependencies->attach($dependency);
    }

    /**
     * @param int $integerVersion
     */
    public function setIntegerVersion($integerVersion)
    {
        $this->integerVersion = $integerVersion;
    }

    /**
     * @return int
     */
    public function getIntegerVersion()
    {
        return $this->integerVersion;
    }

    /**
     * @param int $reviewState
     */
    public function setReviewState($reviewState)
    {
        $this->reviewState = $reviewState;
    }

    /**
     * @return int
     */
    public function getReviewState()
    {
        return $this->reviewState;
    }

    /**
     * @param int $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param int $alldownloadcounter
     */
    public function setAlldownloadcounter($alldownloadcounter)
    {
        $this->alldownloadcounter = $alldownloadcounter;
    }

    /**
     * @return int
     */
    public function getAlldownloadcounter()
    {
        return $this->alldownloadcounter;
    }

    /**
     * @return string
     */
    public function getDocumentationLink(): string
    {
        return $this->documentationLink;
    }

    /**
     * @param string $documentationLink
     */
    public function setDocumentationLink(string $documentationLink): void
    {
        $this->documentationLink = $documentationLink;
    }

    public function getRemoteIdentifier(): string
    {
        return $this->remote;
    }

    /**
     * Map a legacy extension array to an object
     *
     * @param array $extensionArray
     * @return Extension
     */
    public static function createFromExtensionArray(array $extensionArray): self
    {
        $extension = GeneralUtility::makeInstance(self::class);
        $extension->setExtensionKey($extensionArray['key']);
        if (isset($extensionArray['version'])) {
            $extension->setVersion($extensionArray['version']);
        }
        $extension->remote = $extensionArray['remote'] ?? 'ter';
        if (isset($extensionArray['constraints'])) {
            $extension->setDependencies($extension->convertDependenciesToObjects(is_array($extensionArray['constraints']) ? serialize($extensionArray['constraints']) : $extensionArray['constraints']));
        }
        return $extension;
    }

    /**
     * Converts string dependencies to an object storage of dependencies
     *
     * @param string $dependencies
     * @return \SplObjectStorage<Dependency>
     */
    protected function convertDependenciesToObjects(string $dependencies): \SplObjectStorage
    {
        $dependenciesObject = new \SplObjectStorage();
        $unserializedDependencies = unserialize($dependencies, ['allowed_classes' => false]);
        if (!is_array($unserializedDependencies)) {
            return $dependenciesObject;
        }
        foreach ($unserializedDependencies as $dependencyType => $dependencyValues) {
            // Dependencies might be given as empty string, e.g. conflicts => ''
            if (!is_array($dependencyValues)) {
                continue;
            }
            if (!$dependencyType) {
                continue;
            }
            foreach ($dependencyValues as $dependency => $versionConstraint) {
                if ($dependency) {
                    $dependencyObject = Dependency::createFromEmConf((string)$dependency, $versionConstraint, (string)$dependencyType);
                    $dependenciesObject->attach($dependencyObject);
                }
            }
        }
        return $dependenciesObject;
    }

    public function setDistributionImage(string $imageUrl): void
    {
        $this->distributionImage = $imageUrl;
    }

    public function getDistributionImage(): string
    {
        return $this->distributionImage;
    }

    public function setDistributionWelcomeImage(string $imageUrl): void
    {
        $this->distributionWelcomeImage = $imageUrl;
    }

    public function getDistributionWelcomeImage(): string
    {
        return $this->distributionWelcomeImage;
    }
}
