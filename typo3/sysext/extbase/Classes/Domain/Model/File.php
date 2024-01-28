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
 * A file object (File Abstraction Layer)
 */
class File extends AbstractEntity
{
    private ?\TYPO3\CMS\Core\Resource\File $originalResource = null;

    public function getOriginalResource(): \TYPO3\CMS\Core\Resource\File
    {
        if ($this->originalResource === null) {
            $this->originalResource = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObject($this->getUid());
        }

        return $this->originalResource;
    }

    public function setOriginalResource(\TYPO3\CMS\Core\Resource\File $originalResource): void
    {
        $this->originalResource = $originalResource;
    }
}
