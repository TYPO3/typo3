<?php

declare(strict_types=1);

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
    public const DISTRIBUTION_CATEGORY = 10;

    /**
     * Contains default categories.
     */
    protected static array $defaultCategories = [
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
     */
    protected static array $defaultStates = [
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

    protected string $extensionKey = '';
    protected string $version = '';
    protected int $integerVersion = 0;
    protected string $title = '';
    protected string $description = '';
    protected int $state = 0;
    protected int $category = 0;
    protected ?\DateTime $lastUpdated;
    protected string $updateComment = '';
    protected string $authorName = '';
    protected string $authorEmail = '';
    protected bool $currentVersion = false;
    protected string $md5hash = '';
    protected int $reviewState;
    protected int $alldownloadcounter;
    protected string $serializedDependencies = '';

    /**
     * @var \SplObjectStorage<Dependency>
     */
    protected ?\SplObjectStorage $dependencies = null;
    protected string $documentationLink = '';
    protected string $distributionImage = '';
    protected string $distributionWelcomeImage = '';
    protected string $remote;

    /**
     * @internal
     */
    protected int $position = 0;

    public function setAuthorEmail(string $authorEmail): void
    {
        $this->authorEmail = $authorEmail;
    }

    public function getAuthorEmail(): string
    {
        return $this->authorEmail;
    }

    public function setAuthorName(string $authorName): void
    {
        $this->authorName = $authorName;
    }

    public function getAuthorName(): string
    {
        return $this->authorName;
    }

    public function setCategory(int $category): void
    {
        $this->category = $category;
    }

    public function getCategory(): int
    {
        return $this->category;
    }

    /**
     * Get Category String
     */
    public function getCategoryString(): string
    {
        $categoryString = '';
        if (isset(self::$defaultCategories[$this->category])) {
            $categoryString = self::$defaultCategories[$this->category];
        }
        return $categoryString;
    }

    /**
     * Returns category index from a given string or an integer.
     * Fallback to 4 - 'misc' in case string is not found or integer ist out of range.
     */
    public function getCategoryIndexFromStringOrNumber(mixed $category): int
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

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setExtensionKey(string $extensionKey): void
    {
        $this->extensionKey = $extensionKey;
    }

    public function getExtensionKey(): string
    {
        return $this->extensionKey;
    }

    public function setLastUpdated(\DateTime $lastUpdated): void
    {
        $this->lastUpdated = $lastUpdated;
    }

    public function getLastUpdated(): \DateTime
    {
        return $this->lastUpdated;
    }

    public function setState(int $state): void
    {
        $this->state = $state;
    }

    public function getState(): int
    {
        return $this->state;
    }

    public function getStateString(): string
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
     */
    public function getDefaultState(int|string|null $state = null): mixed
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

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setUpdateComment(string $updateComment): void
    {
        $this->updateComment = $updateComment;
    }

    public function getUpdateComment(): string
    {
        return $this->updateComment;
    }

    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setCurrentVersion(bool $currentVersion): void
    {
        $this->currentVersion = $currentVersion;
    }

    public function getCurrentVersion(): bool
    {
        return $this->currentVersion;
    }

    public function setMd5hash(string $md5hash): void
    {
        $this->md5hash = $md5hash;
    }

    public function getMd5hash(): string
    {
        return $this->md5hash;
    }

    /**
     * Possible install paths
     *
     * @static
     */
    public static function returnInstallPaths(): array
    {
        return [
            'System' => Environment::getFrameworkBasePath() . '/',
            'Local' => Environment::getExtensionsPath() . '/',
        ];
    }

    /**
     * Allowed install names: System, Local
     *
     * @static
     */
    public static function returnAllowedInstallTypes(): array
    {
        $installPaths = self::returnInstallPaths();
        return array_keys($installPaths);
    }

    public function setSerializedDependencies(string $dependencies): void
    {
        $this->serializedDependencies = $dependencies;
    }

    public function getSerializedDependencies(): string
    {
        return $this->serializedDependencies;
    }

    /**
     * @param \SplObjectStorage<Dependency> $dependencies
     */
    public function setDependencies(\SplObjectStorage $dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @return \SplObjectStorage<Dependency>
     */
    public function getDependencies(): \SplObjectStorage
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

    public function addDependency(Dependency $dependency): void
    {
        $this->dependencies->attach($dependency);
    }

    public function setIntegerVersion(int $integerVersion): void
    {
        $this->integerVersion = $integerVersion;
    }

    public function getIntegerVersion(): int
    {
        return $this->integerVersion;
    }

    public function setReviewState(int $reviewState): void
    {
        $this->reviewState = $reviewState;
    }

    public function getReviewState(): int
    {
        return $this->reviewState;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setAlldownloadcounter(int $alldownloadcounter): void
    {
        $this->alldownloadcounter = $alldownloadcounter;
    }

    public function getAlldownloadcounter(): int
    {
        return $this->alldownloadcounter;
    }

    public function getDocumentationLink(): string
    {
        return $this->documentationLink;
    }

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
