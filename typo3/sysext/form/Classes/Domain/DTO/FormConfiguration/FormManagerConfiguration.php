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

namespace TYPO3\CMS\Form\Domain\DTO\FormConfiguration;

/**
 * Typed representation of the "formManager" section of the form
 * YAML configuration.
 *
 * @internal
 */
final readonly class FormManagerConfiguration
{
    use ConfigurationValueNormalizationTrait;

    /**
     * @param array<string, string> $dynamicJavaScriptModules JavaScript modules loaded by the form manager, keyed by role (e.g. "app", "viewModel")
     * @param list<string> $stylesheets Stylesheets loaded in the form manager module
     * @param array<int, string> $translationFiles Translation files keyed by priority
     * @param list<SelectablePrototypeConfiguration> $selectablePrototypesConfiguration Prototypes selectable when creating a new form
     * @param string|null $deleteActionErrorTitle Translation key for the delete error title
     * @param string|null $deleteActionErrorMessage Translation key for the delete error message
     */
    public function __construct(
        public array $dynamicJavaScriptModules = [],
        public array $stylesheets = [],
        public array $translationFiles = [],
        public array $selectablePrototypesConfiguration = [],
        public ?string $deleteActionErrorTitle = null,
        public ?string $deleteActionErrorMessage = null,
    ) {}

    /**
     * Create the DTO from the raw "formManager" configuration array.
     *
     * @param array<string, mixed> $configuration
     */
    public static function fromArray(array $configuration): self
    {
        $selectablePrototypesConfiguration = [];
        foreach ($configuration['selectablePrototypesConfiguration'] ?? [] as $key => $prototypeConfiguration) {
            if (is_int($key) && is_array($prototypeConfiguration)) {
                $selectablePrototypesConfiguration[] = SelectablePrototypeConfiguration::fromArray($prototypeConfiguration);
            }
        }

        return new self(
            dynamicJavaScriptModules: self::normalizeStringMap($configuration['dynamicJavaScriptModules'] ?? null),
            stylesheets: self::normalizeStringList($configuration['stylesheets'] ?? null),
            translationFiles: self::normalizeTranslationFiles($configuration['translationFiles'] ?? null),
            selectablePrototypesConfiguration: $selectablePrototypesConfiguration,
            deleteActionErrorTitle: isset($configuration['controller']['deleteAction']['errorTitle'])
                ? (string)$configuration['controller']['deleteAction']['errorTitle']
                : null,
            deleteActionErrorMessage: isset($configuration['controller']['deleteAction']['errorMessage'])
                ? (string)$configuration['controller']['deleteAction']['errorMessage']
                : null,
        );
    }

    /**
     * Return all identifiers defined within "selectablePrototypesConfiguration.*.identifier".
     *
     * @return list<string>
     */
    public function getSelectablePrototypeIdentifiers(): array
    {
        return array_values(array_filter(array_map(
            static fn(SelectablePrototypeConfiguration $prototype): string => $prototype->identifier,
            $this->selectablePrototypesConfiguration,
        )));
    }

    /**
     * Return the prototype configuration for the given identifier, if configured.
     */
    public function getSelectablePrototype(string $identifier): ?SelectablePrototypeConfiguration
    {
        foreach ($this->selectablePrototypesConfiguration as $prototype) {
            if ($prototype->identifier === $identifier) {
                return $prototype;
            }
        }
        return null;
    }

    /**
     * Check whether the given template path is configured for the given prototype.
     *
     * Multiple entries may share the same identifier (e.g. when configuration
     * from different sources is merged), so all matching prototypes are checked.
     */
    public function hasTemplatePath(string $prototypeIdentifier, string $templatePath): bool
    {
        foreach ($this->selectablePrototypesConfiguration as $prototype) {
            if ($prototype->identifier === $prototypeIdentifier && $prototype->hasTemplatePath($templatePath)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Reproduce the raw "selectablePrototypesConfiguration" array, e.g. to hand
     * it over to the JavaScript form manager app.
     *
     * @return list<array<string, mixed>>
     */
    public function getSelectablePrototypesConfigurationAsArray(): array
    {
        return array_map(
            static fn(SelectablePrototypeConfiguration $prototype): array => $prototype->toArray(),
            $this->selectablePrototypesConfiguration,
        );
    }
}
