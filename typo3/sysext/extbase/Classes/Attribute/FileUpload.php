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
    public function __construct(
        public readonly array $validation,
        public readonly string $uploadFolder = '',
        public readonly bool $addRandomSuffix = true,
        public readonly bool $createUploadFolderIfNotExist = true,
        public readonly DuplicationBehavior $duplicationBehavior = DuplicationBehavior::REPLACE,
    ) {}
}
