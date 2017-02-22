<?php
namespace TYPO3\CMS\About\Domain\Repository;

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
use TYPO3\CMS\About\Domain\Model\Extension;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Repository for TYPO3\CMS\About\Domain\Model\Extension
 */
class ExtensionRepository extends Repository
{
    /**
     * Finds all loaded third-party extensions (no system extensions)
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\About\Domain\Model\Extension>
     */
    public function findAllLoaded()
    {
        $loadedExtensions = $this->objectManager->get(ObjectStorage::class);
        $packageManager = $this->objectManager->get(PackageManager::class);
        foreach ($packageManager->getActivePackages() as $package) {
            if ($package->getValueFromComposerManifest('type') === 'typo3-cms-extension') {
                $extension = $this->objectManager->get(Extension::class);
                $extension->setKey($package->getPackageKey());
                $extension->setTitle($package->getPackageMetaData()->getDescription());
                $extension->setAuthors($package->getValueFromComposerManifest('authors'));
                $loadedExtensions->attach($extension);
            }
        }
        return $loadedExtensions;
    }
}
