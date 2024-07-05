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

#[AsTaggedItem(index: 'bool')]
readonly class BoolType implements SettingsTypeInterface
{
    /** @var array<string, bool> */
    private array $stringMap;

    public function __construct(
        protected LoggerInterface $logger,
    ) {
        $this->stringMap = [
            '0' => false,
            '1' => true,
            'false' => false,
            'true' => true,
            'off' => false,
            'on' => true,
            'no' => false,
            'yes' => true,
        ];
    }

    public function validate(mixed $value, SettingDefinition $definition): bool
    {
        if (is_bool($value)) {
            return true;
        }
        if (is_int($value) && ($value === 0 || $value === 1)) {
            return true;
        }
        if (is_string($value) && isset($this->stringMap[$value])) {
            return true;
        }
        return false;
    }

    public function transformValue(mixed $value, SettingDefinition $definition): bool
    {
        if (!$this->validate($value, $definition)) {
            $this->logger->warning('Setting validation field, reverting to default: {key}', ['key' => $definition->key]);
            return $definition->default;
        }
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value)) {
            return (bool)$value;
        }
        if (is_string($value)) {
            return $this->stringMap[$value] ?? false;
        }
        return false;
    }

    public function getJavaScriptModule(): string
    {
        return '@typo3/backend/settings/type/bool.js';
    }
}
