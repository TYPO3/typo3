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

namespace TYPO3\CMS\Core\Settings\Type;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use TYPO3\CMS\Core\Settings\SettingDefinition;
use TYPO3\CMS\Core\Settings\SettingsTypeInterface;
use TYPO3\CMS\Core\Settings\SettingsTypeOption;
use TYPO3\CMS\Core\Settings\SettingsTypeOptionAwareInterface;

#[AsTaggedItem(index: 'url')]
readonly class UrlType implements SettingsTypeInterface, SettingsTypeOptionAwareInterface
{
    public function __construct(
        protected LoggerInterface $logger,
    ) {}

    public function validate(mixed $value, SettingDefinition $definition): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!is_string($value)) {
            return false;
        }

        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        // Check optional constraints
        if (array_key_exists('pattern', $definition->options) &&
            preg_match('/' . str_replace('/', '\/', $definition->options['pattern']) . '/', $value) !== 1
        ) {
            return false;
        }

        return true;
    }

    public function transformValue(mixed $value, SettingDefinition $definition): string
    {
        if (!$this->validate($value, $definition)) {
            $this->logger->warning('Invalid URL, reverting to default: {key}', ['key' => $definition->key]);
            return (string)$definition->default;
        }

        return (string)$value;
    }

    public function getSupportedOptions(): array
    {
        return [
            'pattern' => new SettingsTypeOption(
                type: 'string',
                description: 'Regular expression pattern for URL validation',
                required: false,
            ),
        ];
    }

    public function validateOptions(SettingDefinition $definition): bool
    {
        if (array_key_exists('pattern', $definition->options) &&
            @preg_match('/' . str_replace('/', '\/', $definition->options['pattern']) . '/', '') === false
        ) {
            return false;
        }
        return true;
    }

    public function getJavaScriptModule(): string
    {
        return '@typo3/backend/settings/type/url.js';
    }
}
