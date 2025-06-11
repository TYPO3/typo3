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

namespace TYPO3\CMS\Core\Settings;

/**
 * @internal
 */
final readonly class Settings implements SettingsInterface
{
    public function __construct(
        private array $settings,
    ) {}

    public function has(string $identifier): bool
    {
        return array_key_exists($identifier, $this->settings);
    }

    public function get(string $identifier): mixed
    {
        if (!$this->has($identifier)) {
            throw new SettingNotFoundException('Setting does not exist', 1709555772);
        }
        return $this->settings[$identifier];
    }

    public function getIdentifiers(): array
    {
        return array_keys($this->settings);
    }

    public static function __set_state(array $state): static
    {
        return new static(...$state);
    }
}
