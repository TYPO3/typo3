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

#[AsTaggedItem(index: 'stringlist')]
readonly class StringListType implements SettingsTypeInterface
{
    public function __construct(
        protected LoggerInterface $logger,
    ) {}

    public function validate(mixed $value, SettingDefinition $definition): bool
    {
        $value = $this->decodeJsonAsFallback($value);
        if (!is_array($value)) {
            return false;
        }
        return $this->doValidate(new StringType($this->logger), $value, $definition);
    }

    public function transformValue(mixed $value, SettingDefinition $definition): array
    {
        $stringType = new StringType($this->logger);
        $value = $this->decodeJsonAsFallback($value);
        if (!is_array($value) || !$this->doValidate($stringType, $value, $definition)) {
            $this->logger->warning('Setting validation field, reverting to default: {key}', ['key' => $definition->key]);
            return $definition->default;
        }

        return array_map(static fn(mixed $v): string => $stringType->transformValue($v, $definition), $value);
    }

    public function doValidate(StringType $stringType, array $value, SettingDefinition $definition): bool
    {
        if (!array_is_list($value)) {
            return false;
        }
        foreach ($value as $v) {
            if (!$stringType->validate($v, $definition)) {
                return false;
            }
        }
        return true;
    }

    public function getJavaScriptModule(): string
    {
        return '@typo3/backend/settings/type/stringlist.js';
    }

    private function decodeJsonAsFallback(mixed $value): mixed
    {
        if (is_array($value)) {
            return $value;
        }

        // Otherwise, check if given string value is a json-encoded string
        if (is_string($value)) {
            try {
                // A json-encoded stringlist only needs 2-levels
                $value = json_decode($value, false, 2, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return null;
            }
        }

        return $value;
    }
}
