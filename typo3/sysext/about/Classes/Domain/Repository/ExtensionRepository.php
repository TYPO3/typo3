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
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Repository for TYPO3\CMS\About\Domain\Model\Extension
 */
class ExtensionRepository extends Repository
{
    /**
     * Finds all loaded extensions
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\About\Domain\Model\Extension>
     */
    public function findAllLoaded()
    {
        $loadedExtensions = $this->objectManager->get(ObjectStorage::class);
        $loadedExtensionsArray = $GLOBALS['TYPO3_LOADED_EXT'];
        foreach ($loadedExtensionsArray as $extensionKey => $extension) {
            if ((is_array($extension) || $extension instanceof \ArrayAccess) && $extension['type'] !== 'S') {
                $emconfPath = PATH_site . $extension['siteRelPath'] . 'ext_emconf.php';
                if (file_exists($emconfPath)) {
                    include $emconfPath;
                    $extension = $this->objectManager->get(Extension::class);
                    $extension->setKey($extensionKey);
                    $extension->setTitle($EM_CONF['']['title']);
                    $extension->setAuthor($EM_CONF['']['author']);
                    $extension->setAuthorEmail($EM_CONF['']['author_email']);
                    $loadedExtensions->attach($extension);
                }
            }
        }
        return $loadedExtensions;
    }
}
