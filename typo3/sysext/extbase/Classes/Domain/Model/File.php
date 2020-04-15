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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A file object (File Abstraction Layer)
 *
 * @internal experimental! This class is experimental and subject to change!
 */
class File extends AbstractFileFolder
{
    /**
     * @return \TYPO3\CMS\Core\Resource\File
     */
    public function getOriginalResource()
    {
        if ($this->originalResource === null) {
            $this->originalResource = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObject($this->getUid());
        }

        return $this->originalResource;
    }
}
