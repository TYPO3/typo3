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

namespace TYPO3\CMS\Extensionmanager\Remote;

use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extensionmanager\Domain\Model\Dependency;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Enum\ExtensionCategory;
use TYPO3\CMS\Extensionmanager\Enum\ExtensionState;
use TYPO3\CMS\Extensionmanager\Remote\Dto\TerExtension;
use TYPO3\CMS\Extensionmanager\Remote\Dto\TerExtensionVersion;

/**
 * Turns what the TER REST API reports into the extension model the rest of the
 * Extension Manager works with.
 *
 * This deliberately does not live on the model: the wire format of one remote is
 * not something the domain should know. A second remote - the FAIR beacon, say,
 * whose documents carry neither category nor owner nor review state - brings its
 * own value objects and its own mapper, rather than another factory on Extension.
 *
 * @internal This class is a specific ExtensionManager implementation and is not part of the Public TYPO3 API.
 */
readonly class TerExtensionMapper
{
    /**
     * The remote identifier is kept so downloading still resolves the registered
     * remote for the package, which is what fetches and verifies the archive.
     */
    public function map(
        TerExtension $remoteExtension,
        ?TerExtensionVersion $version = null,
        string $remoteIdentifier = 'ter',
    ): Extension {
        $version ??= $remoteExtension->currentVersion;
        $extension = new Extension();
        $extension->extensionKey = $remoteExtension->key;
        $extension->remote = $remoteIdentifier;
        $extension->ownerusername = $remoteExtension->owner;
        $extension->alldownloadcounter = $remoteExtension->downloads;
        $extension->documentationLink = $remoteExtension->documentationLink;
        $extension->composerName = $remoteExtension->composerName;
        if ($version !== null) {
            $extension->version = $version->number;
            $extension->integerVersion = VersionNumberUtility::convertVersionNumberToInteger($version->number);
            $extension->title = $version->title;
            $extension->description = $version->description;
            $extension->updateComment = $version->uploadComment;
            $extension->state = ExtensionState::fromValue($version->state)->value;
            $extension->category = ExtensionCategory::fromValue($version->category)->value;
            $extension->lastUpdated = $version->uploadDate;
            $extension->reviewState = $version->reviewState;
            $extension->authorName = $version->author->name ?? '';
            $extension->authorEmail = $version->author->email ?? '';
            $extension->artifactSha256 = $version->artifactSha256;
            $extension->currentVersion = $version->number === ($remoteExtension->currentVersion->number ?? null);
            $extension->setDependencies($this->buildDependencies($version));
        }
        return $extension;
    }

    /**
     * @return \SplObjectStorage<Dependency>
     */
    private function buildDependencies(TerExtensionVersion $version): \SplObjectStorage
    {
        $storage = new \SplObjectStorage();
        foreach ($version->dependencies as $identifier => $constraint) {
            $storage->offsetSet(Dependency::createFromEmConf((string)$identifier, $this->toEmConfConstraint((string)$constraint), 'depends'));
        }
        foreach ($version->conflicts as $identifier => $constraint) {
            $storage->offsetSet(Dependency::createFromEmConf((string)$identifier, $this->toEmConfConstraint((string)$constraint), 'conflicts'));
        }
        return $storage;
    }

    /**
     * The API sends a range as ">=13.4.29 <=14.3.99", while Dependency reads the
     * ext_emconf.php notation "13.4.29-14.3.99".
     */
    private function toEmConfConstraint(string $constraint): string
    {
        if (!preg_match_all('/(>=|<=)\s*(\d[\d.]*)/', $constraint, $matches, PREG_SET_ORDER)) {
            return $constraint;
        }
        $bounds = ['>=' => '', '<=' => ''];
        foreach ($matches as [, $operator, $versionNumber]) {
            $bounds[$operator] = $versionNumber;
        }
        return rtrim($bounds['>='] . '-' . $bounds['<='], '-');
    }
}
