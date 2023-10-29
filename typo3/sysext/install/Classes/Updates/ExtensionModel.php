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

namespace TYPO3\CMS\Install\Updates;

/**
 * Model for extensions installed by upgrade wizards
 *
 * @internal
 */
class ExtensionModel
{
    public function __construct(
        protected readonly string $key,
        protected readonly string $title,
        protected readonly string $versionString,
        protected readonly string $composerName,
        protected readonly string $description
    ) {}

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getVersionString(): string
    {
        return $this->versionString;
    }

    public function getComposerName(): string
    {
        return $this->composerName;
    }
}
