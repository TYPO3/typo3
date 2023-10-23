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

namespace TYPO3\CMS\Install\Service\Event;

/**
 * Event to modify the language pack array
 */
final class ModifyLanguagePacksEvent
{
    public function __construct(private array $extensions) {}

    public function getExtensions(): array
    {
        return $this->extensions;
    }

    public function removeExtension(string $extension): void
    {
        unset($this->extensions[$extension]);
    }

    public function removeIsoFromExtension(string $iso, string $extension): void
    {
        unset($this->extensions[$extension]['packs'][$iso]);
    }
}
