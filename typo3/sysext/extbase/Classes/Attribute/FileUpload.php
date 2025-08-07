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

namespace TYPO3\CMS\Extbase\Attribute;

use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class FileUpload
{
    public array $validation = [];
    public string $uploadFolder = '';
    public bool $addRandomSuffix = true;
    public bool $createUploadFolderIfNotExist = true;
    public DuplicationBehavior $duplicationBehavior = DuplicationBehavior::REPLACE;

    public function __construct(array $values)
    {
        if (isset($values['validation'])) {
            $this->validation = $values['validation'];
        }

        if (isset($values['addRandomSuffix'])) {
            $this->addRandomSuffix = (bool)$values['addRandomSuffix'];
        }

        if (isset($values['uploadFolder'])) {
            $this->uploadFolder = $values['uploadFolder'];
        }

        if (isset($values['createUploadFolderIfNotExist'])) {
            $this->createUploadFolderIfNotExist = (bool)$values['createUploadFolderIfNotExist'];
        }

        if (isset($values['duplicationBehavior'])) {
            if (!$values['duplicationBehavior'] instanceof DuplicationBehavior) {
                throw new \RuntimeException('Wrong annotation configuration for "duplicationBehavior". Ensure, that the value is a valid DuplicationBehavior.', 1711453150);
            }

            $this->duplicationBehavior = $values['duplicationBehavior'];
        }
    }
}
