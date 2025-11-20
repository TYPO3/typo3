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
    public readonly array $validation;
    public readonly string $uploadFolder;
    public readonly bool $addRandomSuffix;
    public readonly bool $createUploadFolderIfNotExist;
    public readonly DuplicationBehavior $duplicationBehavior;

    public function __construct(
        // @todo Use CPP with TYPO3 v15.0
        array $validation,
        string $uploadFolder = '',
        bool $addRandomSuffix = true,
        bool $createUploadFolderIfNotExist = true,
        DuplicationBehavior $duplicationBehavior = DuplicationBehavior::REPLACE,
    ) {
        // @todo Remove with TYPO3 v15.0
        if ($this->containsDeprecatedConfiguration($validation)) {
            trigger_error(
                'Passing an array of configuration values to Extbase attributes will be removed in TYPO3 v15.0. ' .
                'Use explicit constructor parameters instead.',
                E_USER_DEPRECATED,
            );

            $values = $validation;

            $this->validation = $values['validation'] ?? [];
            $this->addRandomSuffix = (bool)($values['addRandomSuffix'] ?? $addRandomSuffix);
            $this->uploadFolder = $values['uploadFolder'] ?? $uploadFolder;
            $this->createUploadFolderIfNotExist = (bool)($values['createUploadFolderIfNotExist'] ?? $createUploadFolderIfNotExist);

            if (isset($values['duplicationBehavior'])) {
                if (!$values['duplicationBehavior'] instanceof DuplicationBehavior) {
                    throw new \RuntimeException('Wrong attribute configuration for "duplicationBehavior". Ensure, that the value is a valid DuplicationBehavior.', 1711453150);
                }

                $this->duplicationBehavior = $values['duplicationBehavior'];
            } else {
                $this->duplicationBehavior = $duplicationBehavior;
            }
        } else {
            $this->validation = $validation;
            $this->uploadFolder = $uploadFolder;
            $this->addRandomSuffix = $addRandomSuffix;
            $this->createUploadFolderIfNotExist = $createUploadFolderIfNotExist;
            $this->duplicationBehavior = $duplicationBehavior;
        }
    }

    protected function containsDeprecatedConfiguration(array $values): bool
    {
        $deprecatedKeys = [
            'validation',
            'addRandomSuffix',
            'uploadFolder',
            'createUploadFolderIfNotExist',
            'duplicationBehavior',
        ];

        return array_intersect_key(array_flip($deprecatedKeys), $values) !== [];
    }
}
