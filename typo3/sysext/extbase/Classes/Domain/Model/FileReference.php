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

namespace TYPO3\CMS\Extbase\Domain\Model;

use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * A file reference object (File Abstraction Layer)
 */
class FileReference extends AbstractEntity
{
    /**
     * Uid of the referenced sys_file. Needed for extbase to serialize the
     * reference correctly.
     */
    protected ?int $uidLocal = null;

    protected ?\TYPO3\CMS\Core\Resource\FileReference $originalResource = null;

    public function setOriginalResource(\TYPO3\CMS\Core\Resource\FileReference $originalResource): void
    {
        $this->originalResource = $originalResource;
        $this->uidLocal = $originalResource->getOriginalFile()->getUid();
    }

    public function getOriginalResource(): \TYPO3\CMS\Core\Resource\FileReference
    {
        if ($this->originalResource === null) {
            $uid = $this->_localizedUid;
            $this->originalResource = GeneralUtility::makeInstance(ResourceFactory::class)->getFileReferenceObject($uid);
        }

        return $this->originalResource;
    }
}
