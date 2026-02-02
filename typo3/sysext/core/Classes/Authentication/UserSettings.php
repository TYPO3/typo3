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

namespace TYPO3\CMS\Core\Authentication;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Authentication\Exception\UserSettingsNotFoundException;

readonly class UserSettings implements ContainerInterface
{
    public function __construct(
        private array $settings,
    ) {}

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->settings);
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->settings)) {
            return $this->settings[$id];
        }
        throw new UserSettingsNotFoundException(
            'User setting "' . $id . '" is not available.',
            1738500000
        );
    }

    public function toArray(): array
    {
        return $this->settings;
    }

    public function isEmailMeAtLoginEnabled(): bool
    {
        return (bool)($this->settings['emailMeAtLogin'] ?? false);
    }

    public function isUploadFieldsInTopOfEBEnabled(): bool
    {
        return (bool)($this->settings['edit_docModuleUpload'] ?? true);
    }
}
