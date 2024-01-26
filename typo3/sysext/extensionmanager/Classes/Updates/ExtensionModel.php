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

namespace TYPO3\CMS\Extensionmanager\Updates;

/**
 * Model for extensions installed by upgrade wizards
 *
 * @internal
 * @todo: Declare 'final readonly' in v14 when ext:install class alias is gone.
 */
class ExtensionModel
{
    public function __construct(
        protected string $key,
        protected string $title,
        protected string $versionString,
        protected string $composerName,
        protected string $description
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
