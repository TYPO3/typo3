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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Enum\ExtensionState;

/**
 * Main extension model.
 *
 * @internal This class is a specific domain model implementation and is not part of the Public TYPO3 API.
 */
class Extension
{
    public int $uid = 0;
    public string $extensionKey = '';
    public string $version = '';
    public int $integerVersion = 0;
    public string $title = '';
    public string $description = '';
    public int $state = 0;
    public int $category = 0;
    public int $lastUpdated = 0;
    public string $updateComment = '';
    public string $authorName = '';
    public string $authorEmail = '';
    public string $ownerusername = '';
    public bool $currentVersion = false;
    public string $md5hash = '';
    public int $reviewState = 0;
    public int $alldownloadcounter = 0;
    public string $serializedDependencies = '';

    /**
     * @var \SplObjectStorage<Dependency>
     */
    public ?\SplObjectStorage $dependencies = null;
    public string $documentationLink = '';
    public string $distributionImage = '';
    public string $distributionWelcomeImage = '';
    public string $remote = '';

    public static function createObjectFromRow(array $row): self
    {
        $extension = new self();
        $extension->uid = (int)($row['uid']);
        $extension->extensionKey = $row['extension_key'] ?? '';
        $extension->remote = $row['remote'] ?? '';
        $extension->version = $row['version'] ?? '';
        $extension->alldownloadcounter = (int)($row['alldownloadcounter'] ?? 0);
        $extension->title = $row['title'] ?? '';
        $extension->description = $row['description'] ?? '';
        $extension->state = (int)($row['state'] ?? 0);
        $extension->category = (int)($row['category'] ?? 0);
        $extension->lastUpdated = (int)($row['last_updated'] ?? 0);
        $extension->updateComment = $row['update_comment'] ?? '';
        $extension->serializedDependencies = $row['serialized_dependencies'] ?? '';
        $extension->authorName = $row['author_name'] ?? '';
        $extension->authorEmail = $row['author_email'] ?? '';
        $extension->ownerusername = $row['ownerusername'] ?? '';
        $extension->currentVersion = (bool)($row['current_version'] ?? false);
        $extension->md5hash = $row['md5hash'] ?? '';
        $extension->reviewState = (int)($row['review_state'] ?? 0);
        $extension->integerVersion = (int)($row['integer_version'] ?? 0);
        $extension->documentationLink = $row['documentation_link'] ?? '';
        $extension->distributionImage = $row['distribution_image'] ?? '';
        $extension->distributionWelcomeImage = $row['distribution_welcome_image'] ?? '';
        $extension->dependencies = null;

        return $extension;
    }

    public function getStateString(): string
    {
        $state = ExtensionState::from($this->state);
        return $state->getStringValue();
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
            $this->setDependencies($this->convertDependenciesToObjects($this->serializedDependencies));
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
     * Map a legacy extension array to an object
     */
    public static function createFromExtensionArray(array $extensionArray): self
    {
        $extension = GeneralUtility::makeInstance(self::class);
        $extension->extensionKey = $extensionArray['key'];
        if (isset($extensionArray['version'])) {
            $extension->version = $extensionArray['version'];
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
                    $dependenciesObject->offsetSet($dependencyObject);
                }
            }
        }
        return $dependenciesObject;
    }
}
