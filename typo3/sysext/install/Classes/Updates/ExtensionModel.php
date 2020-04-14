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
    /**
     * @var string
     */
    protected $key = '';

    /**
     * @var string
     */
    protected $title = '';

    /**
     * @var string
     */
    protected $versionString = '';

    /**
     * @var string
     */
    protected $composerName = '';

    /**
     * @var string
     */
    protected $description = '';

    public function __construct(
        string $key,
        string $title,
        string $versionString,
        string $composerName,
        string $description
    ) {
        $this->key = $key;
        $this->title = $title;
        $this->versionString = $versionString;
        $this->composerName = $composerName;
        $this->description = $description;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getVersionString(): string
    {
        return $this->versionString;
    }

    /**
     * @return string
     */
    public function getComposerName(): string
    {
        return $this->composerName;
    }
}
