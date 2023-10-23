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

namespace TYPO3\CMS\Core\TypoScript\IncludeTree\Event;

/**
 * Extensions can add global user TSconfig right before they are loaded from other sources
 * like the global user.tsconfig file.
 *
 * Note: The added config should not depend on runtime / request. This is considered static
 *       config and thus should be identical on every request.
 */
final class BeforeLoadedUserTsConfigEvent
{
    public function __construct(private array $tsConfig = []) {}

    public function getTsConfig(): array
    {
        return $this->tsConfig;
    }

    public function addTsConfig(string $tsConfig): void
    {
        $this->tsConfig[] = $tsConfig;
    }

    public function setTsConfig(array $tsConfig): void
    {
        $this->tsConfig = $tsConfig;
    }
}
