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

namespace TYPO3\CMS\Core\Site\Set;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Settings\CategoryDefinition;
use TYPO3\CMS\Core\Settings\SettingDefinition;
use TYPO3\CMS\Core\Settings\SettingsTypeRegistry;

/**
 * @internal
 */
#[Autoconfigure(public: true)]
class YamlSetDefinitionProvider
{
    public function __construct(
        protected readonly SettingsTypeRegistry $settingsTypeRegistry
    ) {}

    /** @var array<string, SetDefinition> */
    protected array $sets = [];

    /**
     * @return array<string, SetDefinition>
     */
    public function getSetDefinitions(): array
    {
        return $this->sets;
    }

    public function addSet(SetDefinition $set): void
    {
        $this->sets[$set->name] = $set;
    }

    public function get(\SplFileInfo $fileInfo, string $errorContext): SetDefinition
    {
        $filename = $fileInfo->getPathname();
        // No placeholders or imports processed on purpose
        // Use dependencies for shared sets
        try {
            $set = Yaml::parseFile($filename);
        } catch (ParseException $e) {
            $source = $errorContext . basename($filename);
            throw new InvalidSetException('Failed to parse set definition from "' . $source . '": ' . $e->getMessage(), 1711024370, $e);
        }
        $path = dirname($filename);
        $setName = $set['name'] ?? '';

        $settingsDefinitionsFile = $path . '/settings.definitions.yaml';
        if (is_file($settingsDefinitionsFile)) {
            try {
                $settingsDefinitions = Yaml::parseFile($settingsDefinitionsFile);
            } catch (ParseException $e) {
                $source = $errorContext . basename($settingsDefinitionsFile);
                throw new InvalidSettingsDefinitionsException(
                    'Invalid settings definition. Source: ' . $source,
                    1711024374,
                    $e,
                    $setName
                );
            }
            if (!is_array($settingsDefinitions['settings'] ?? null)) {
                $source = $errorContext . basename($settingsDefinitionsFile);
                throw new InvalidSettingsDefinitionsException(
                    'Missing "settings" key in settings definitions. Source: ' . $source,
                    1711024378,
                    null,
                    $setName
                );
            }
            $set['settingsDefinitions'] = $settingsDefinitions['settings'] ?? [];
            $set['categoryDefinitions'] = $settingsDefinitions['categories'] ?? [];
        }

        $settingsFile = $path . '/settings.yaml';
        if (is_file($settingsFile)) {
            try {
                $settings = Yaml::parseFile($settingsFile);
            } catch (ParseException $e) {
                $source = $errorContext . basename($settingsFile);
                throw new InvalidSettingsException('Invalid settings format. Source: ' . $source, 1711024380, $e, $setName);
            }
            $settings ??= [];
            if (!is_array($settings)) {
                $source = $errorContext . basename($settingsFile);
                throw new InvalidSettingsException('Invalid settings format. Source: ' . $source, 1711024382, null, $setName);
            }
            $set['settings'] = $settings;
        }

        if (($set['labels'] ?? '') === '') {
            $labelsFile = $path . '/labels.xlf';
            if (is_file($labelsFile)) {
                $set['labels'] = $labelsFile;
            }
        }

        return $this->createDefinition($set, $path);
    }

    protected function createDefinition(array $set, string $basePath): SetDefinition
    {
        $settingsDefinitions = [];
        $labels = $set['labels'] ?? null;
        unset($set['labels']);

        if ($labels) {
            $set['label'] ??= 'LLL:' . $labels . ':label';
        }

        foreach (($set['settingsDefinitions'] ?? []) as $setting => $options) {
            if ($labels) {
                $options['label'] ??= 'LLL:' . $labels . ':settings.' . $setting;
                $options['description'] ??= 'LLL:' . $labels . ':settings.description.' . $setting;
            }
            $settingDefinitionData = [...['key' => $setting], ...$options];
            try {
                $definition = new SettingDefinition(...$settingDefinitionData);
            } catch (\Error $e) {
                throw new InvalidSettingsDefinitionsException(
                    'Invalid setting definition "' . $setting . '": ' . json_encode($options) . ' – ' . $this->getObjectConstructionErrors($e, SettingDefinition::class, $settingDefinitionData),
                    1702623312,
                    $e,
                    $set['name'] ?? ''
                );
            }
            if (!$this->settingsTypeRegistry->has($definition->type)) {
                throw new InvalidSettingsDefinitionsException('Invalid settings type "' . $definition->type . '" for settings definition: ' . json_encode($options), 1732181103, null, $set['name'] ?? '');
            }
            $type = $this->settingsTypeRegistry->get($definition->type);
            if (!$type->validate($definition->default, $definition)) {
                throw new InvalidSettingsDefinitionsException('Invalid default value for settings definition: ' . json_encode($options), 1732181102, null, $set['name'] ?? '');
            }
            $settingsDefinitions[] = $definition;
        }

        $categoryDefinitions = [];
        foreach (($set['categoryDefinitions'] ?? []) as $category => $options) {
            if ($labels) {
                $options['label'] ??= 'LLL:' . $labels . ':categories.' . $category;
                $options['description'] ??= 'LLL:' . $labels . ':categories.description.' . $category;
            }
            try {
                $definition = new CategoryDefinition(...[...['key' => $category], ...$options]);
            } catch (\Error $e) {
                throw new InvalidCategoryDefinitionsException(
                    'Invalid category definition "' . $category . '": ' . json_encode($options),
                    1702623313,
                    $e,
                    $set['name'] ?? ''
                );
            }
            $categoryDefinitions[] = $definition;
        }

        $setData = [
            ...$set,
            'settingsDefinitions' => $settingsDefinitions,
            'categoryDefinitions' => $categoryDefinitions,
        ];
        $setData['typoscript'] ??= $basePath;
        $setData['pagets'] ??= $basePath . '/page.tsconfig';
        try {
            return new SetDefinition(...$setData);
        } catch (\Error $e) {
            throw new InvalidSetException(
                'Invalid set definition: ' . json_encode($set) . ' – ' . $this->getObjectConstructionErrors($e, SetDefinition::class, $setData),
                1170859526,
                $e,
                $set['name'] ?? ''
            );
        }
    }

    protected function getObjectConstructionErrors(
        \Error $error,
        string $className,
        array $arguments,
    ): string {
        $reflection = new \ReflectionClass($className);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();
        $missingParameters = [];
        $typeErrors = [];
        foreach ($parameters as $parameter) {
            if (isset($arguments[$parameter->name])) {
                $value = $arguments[$parameter->name];
                unset($arguments[$parameter->name]);
                $type = $parameter->getType();
                if (!$this->typeMatches($type, $value)) {
                    $typeErrors[$parameter->name] = (string)$type;
                }
            } elseif (!$parameter->isDefaultValueAvailable()) {
                $missingParameters[] = $parameter->name;
            }
        }

        $errors = [];
        if ($missingParameters !== []) {
            $errors[] = 'Missing properties: ' . implode(', ', $missingParameters);
        }
        if ($arguments !== []) {
            $errors[] = 'Invalid properties: ' . implode(', ', array_keys($arguments));
        }
        if ($typeErrors !== []) {
            $errors[] = 'Invalid type: ' . implode(', ', array_keys($typeErrors));
        }

        if ($errors === []) {
            return $error->getMessage();
        }

        return implode('; ', $errors);
    }

    protected function typeMatches(
        \ReflectionType $type,
        mixed $value
    ): bool {
        if ($type->allowsNull() && $value === null) {
            return true;
        }

        if ($type instanceof \ReflectionUnionType) {
            foreach ($type->getTypes() as $t) {
                if ($this->typeMatches($t, $value)) {
                    return true;
                }
            }
            return false;
        }

        if ($type instanceof \ReflectionIntersectionType) {
            foreach ($type->getTypes() as $t) {
                if (!$this->typeMatches($t, $value)) {
                    return false;
                }
            }
            return true;
        }

        if ($type instanceof \ReflectionNamedType) {
            $typeName = $type->getName();
            $valueType = gettype($value);
            if ($valueType === 'object') {
                return is_subclass_of($value, $typeName);
            }
            return $valueType === $typeName;
        }

        return true;
    }
}
