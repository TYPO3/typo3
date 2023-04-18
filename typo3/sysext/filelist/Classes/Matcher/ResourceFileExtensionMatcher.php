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

namespace TYPO3\CMS\Filelist\Matcher;

use TYPO3\CMS\Core\Resource\File;

/**
 * @internal
 */
class ResourceFileExtensionMatcher implements MatcherInterface
{
    /**
     * @var string[]
     */
    protected array $extensions = [];

    /**
     * @var string[]
     */
    protected array $ignoredExtensions = [];

    /**
     * @param string[] $extensions
     */
    public function setExtensions(array $extensions): self
    {
        $this->extensions = array_map(strtolower(...), $extensions);

        return $this;
    }

    public function addExtension(string $extension): self
    {
        $this->extensions[] = strtolower($extension);

        return $this;
    }

    /**
     * @param string[] $ignoredExtensions
     */
    public function setIgnoredExtensions(array $ignoredExtensions): self
    {
        $this->ignoredExtensions = array_map(strtolower(...), $ignoredExtensions);

        return $this;
    }

    public function addIgnoredExtension(string $ignoredExtension): self
    {
        $this->ignoredExtensions[] = strtolower($ignoredExtension);

        return $this;
    }

    public function supports(mixed $item): bool
    {
        return $item instanceof File;
    }

    public function match(mixed $item): bool
    {
        if (!$item instanceof File) {
            return false;
        }

        if (in_array($item->getExtension(), $this->ignoredExtensions, true)) {
            return false;
        }

        if (in_array('*', $this->extensions, true) || in_array($item->getExtension(), $this->extensions, true)) {
            return true;
        }

        return false;
    }
}
