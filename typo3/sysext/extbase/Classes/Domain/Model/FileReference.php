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

namespace TYPO3\CMS\Extbase\Domain\Model;

use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A file reference object (File Abstraction Layer)
 *
 * @internal experimental! This class is experimental and subject to change!
 */
class FileReference extends AbstractFileFolder
{
    /**
     * Uid of the referenced sys_file. Needed for extbase to serialize the
     * reference correctly.
     *
     * @var int
     */
    protected $uidLocal;

    /**
     * @param \TYPO3\CMS\Core\Resource\ResourceInterface $originalResource
     */
    public function setOriginalResource(ResourceInterface $originalResource)
    {
        $this->originalResource = $originalResource;
        $this->uidLocal = (int)$originalResource->getOriginalFile()->getUid();
    }

    /**
     * @return \TYPO3\CMS\Core\Resource\FileReference
     */
    public function getOriginalResource()
    {
        if ($this->originalResource === null) {
            $uid = $this->_localizedUid;
            $this->originalResource = GeneralUtility::makeInstance(ResourceFactory::class)->getFileReferenceObject($uid);
        }

        return $this->originalResource;
    }
}
