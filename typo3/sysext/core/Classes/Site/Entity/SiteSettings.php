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

namespace TYPO3\CMS\Core\Site\Entity;

use TYPO3\CMS\Core\Settings\Settings;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Entity representing all settings for a site. These settings are not overlaid
 * with TypoScript settings / constants which happens in the TypoScript Parser
 * for a specific page.
 */
final readonly class SiteSettings extends Settings implements \JsonSerializable
{
    private array $flatSettings;

    public function __construct(array $settings)
    {
        parent::__construct($settings);
        $this->flatSettings = $this->isEmpty() ? [] : ArrayUtility::flattenPlain($settings);
    }

    public function has(string $identifier): bool
    {
        return isset($this->settings[$identifier]) || isset($this->flatSettings[$identifier]);
    }

    public function isEmpty(): bool
    {
        return $this->settings === [];
    }

    public function get(string $identifier, mixed $defaultValue = null): mixed
    {
        return $this->settings[$identifier] ?? $this->flatSettings[$identifier] ?? $defaultValue;
    }

    public function getAll(): array
    {
        return $this->settings;
    }

    public function getAllFlat(): array
    {
        return $this->flatSettings;
    }

    public function jsonSerialize(): mixed
    {
        return json_encode($this->settings);
    }

    public static function __set_state(array $state): self
    {
        return new self($state['settings'] ?? []);
    }
}
