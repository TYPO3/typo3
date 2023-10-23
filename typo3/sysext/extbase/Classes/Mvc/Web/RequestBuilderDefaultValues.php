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

namespace TYPO3\CMS\Extbase\Mvc\Web;

/**
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
final class RequestBuilderDefaultValues
{
    /**
     * @param non-empty-string $extensionName
     * @param non-empty-string $pluginName
     * @param class-string $defaultControllerClassName
     * @param non-empty-string $defaultControllerAlias
     * @param non-empty-string $defaultFormat
     */
    private function __construct(
        private readonly string $extensionName,
        private readonly string $pluginName,
        private readonly string $defaultControllerClassName,
        private readonly string $defaultControllerAlias,
        private readonly string $defaultFormat,
        private readonly array $allowedControllerActions,
        private readonly array $controllerAliasToClassMapping,
        private readonly array $controllerClassToAliasMapping,
    ) {}

    public static function fromConfiguration(array $configuration): self
    {
        $extensionName = $configuration['extensionName'] ?? null;
        $extensionName = is_string($extensionName) && $extensionName !== '' ? $extensionName : null;

        $pluginName = $configuration['pluginName'] ?? null;
        $pluginName = is_string($pluginName) && $pluginName !== '' ? $pluginName : null;

        $controllerConfigurations = $configuration['controllerConfiguration'] ?? [];
        $controllerConfigurations = is_array($controllerConfigurations) ? $controllerConfigurations : [];

        if (!is_string($extensionName)) {
            throw new \InvalidArgumentException('"extensionName" is not properly configured. Request can\'t be dispatched!', 1289843275);
        }
        if (!is_string($pluginName)) {
            throw new \InvalidArgumentException('"pluginName" is not properly configured. Request can\'t be dispatched!', 1289843277);
        }

        if ($controllerConfigurations === []) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The default controller for extension "%s" and plugin "%s" can not be determined. ' .
                    'Please check for TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin() in your ext_localconf.php.',
                    $extensionName,
                    $pluginName
                ),
                1316104317
            );
        }

        $defaultFormat = $configuration['format'] ?? null;
        $defaultFormat = is_string($defaultFormat) && $defaultFormat !== '' ? $defaultFormat : 'html';

        $defaultControllerClassName = null;
        $defaultControllerAlias = null;

        $allowedControllerActions = [];
        $controllerClassToAliasMapping = [];
        $controllerAliasToClassMapping = [];

        $firstItem = true;
        foreach ($controllerConfigurations as $controllerClassName => $controllerConfiguration) {
            if (!is_string($controllerClassName) || $controllerClassName === '') {
                continue;
            }

            if (!is_array($controllerConfiguration)) {
                continue;
            }

            $actions = $controllerConfiguration['actions'] ?? [];
            $actions = is_array($actions) ? $actions : [];

            if ($actions === []) {
                continue;
            }

            $controllerClassName = $controllerConfiguration['className'] ?? null;
            $controllerClassName = is_string($controllerClassName) && $controllerClassName !== '' ? $controllerClassName : null;

            if ($controllerClassName === null) {
                continue;
            }

            $controllerAlias = $controllerConfiguration['alias'] ?? null;
            $controllerAlias = is_string($controllerAlias) && $controllerAlias !== '' ? $controllerAlias : null;

            if ($controllerAlias === null) {
                continue;
            }

            $allowedControllerActions[$controllerClassName] = $actions;
            $controllerClassToAliasMapping[$controllerClassName] = $controllerAlias;
            $controllerAliasToClassMapping[$controllerAlias] = $controllerClassName;

            if ($firstItem) {
                $defaultControllerClassName = $controllerClassName;
                $defaultControllerAlias = $controllerAlias;
            }

            $firstItem = false;
        }

        if ($defaultControllerClassName === null || $defaultControllerAlias === null) {
            throw new \LogicException(
                'Either $defaultControllerClassName or $defaultControllerAlias are unexpectedly null',
                1679051921
            );
        }

        if ($allowedControllerActions === []) {
            throw new \LengthException(
                '$allowedControllerActions is expected to not be empty',
                1679051891
            );
        }

        return new self(
            $extensionName,
            $pluginName,
            $defaultControllerClassName,
            $defaultControllerAlias,
            $defaultFormat,
            $allowedControllerActions,
            $controllerAliasToClassMapping,
            $controllerClassToAliasMapping,
        );
    }

    /**
     * @return non-empty-string
     */
    public function getExtensionName(): string
    {
        return $this->extensionName;
    }

    /**
     * @return non-empty-string
     */
    public function getPluginName(): string
    {
        return $this->pluginName;
    }

    /**
     * @return class-string
     */
    public function getDefaultControllerClassName(): string
    {
        return $this->defaultControllerClassName;
    }

    /**
     * @return non-empty-string
     */
    public function getDefaultControllerAlias(): string
    {
        return $this->defaultControllerAlias;
    }

    /**
     * @return non-empty-string
     */
    public function getDefaultFormat(): string
    {
        return $this->defaultFormat;
    }

    /**
     * @return array<class-string, list<string>>
     */
    public function getAllowedControllerActions(): array
    {
        return $this->allowedControllerActions;
    }

    /**
     * @return list<string>
     */
    public function getAllowedControllerActionsOfController(string $controllerClassName): array
    {
        return $this->allowedControllerActions[$controllerClassName] ?? [];
    }

    /**
     * @return array<non-empty-string, class-string>
     */
    public function getControllerAliasToClassMapping(): array
    {
        return $this->controllerAliasToClassMapping;
    }

    /**
     * @return array<class-string, non-empty-string>
     */
    public function getControllerClassToAliasMapping(): array
    {
        return $this->controllerClassToAliasMapping;
    }

    /**
     * @param non-empty-string $controllerAlias
     * @return class-string|null
     */
    public function getControllerClassNameForAlias(string $controllerAlias): ?string
    {
        return $this->controllerAliasToClassMapping[$controllerAlias] ?? null;
    }

    /**
     * @param class-string $controllerClassName
     * @return non-empty-string|null
     */
    public function getControllerAliasForControllerClassName(string $controllerClassName): ?string
    {
        return $this->controllerClassToAliasMapping[$controllerClassName] ?? null;
    }

    public function getDefaultActionName(string $controllerClassName): ?string
    {
        $actions = $this->allowedControllerActions[$controllerClassName] ?? [];
        return $actions[0] ?? null;
    }
}
