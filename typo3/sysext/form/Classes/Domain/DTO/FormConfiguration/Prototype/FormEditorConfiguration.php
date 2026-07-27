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

namespace TYPO3\CMS\Form\Domain\DTO\FormConfiguration\Prototype;

use TYPO3\CMS\Form\Domain\DTO\FormConfiguration\ConfigurationValueNormalizationTrait;
use TYPO3\CMS\Form\Domain\DTO\FormConfiguration\RawConfigurationTrait;

/**
 * Typed representation of the "prototypes.<name>.formEditor" section of the
 * form YAML configuration.
 *
 * This is an "open" DTO: the form editor setup is extensible by third-party
 * extensions (custom inspector editors, partials, dynamic modules, …). Only the
 * well-known core keys are exposed as typed properties; everything else stays
 * reachable through {@see RawConfigurationTrait::getRaw()} / ::get().
 *
 * @internal
 */
final readonly class FormEditorConfiguration
{
    use ConfigurationValueNormalizationTrait;
    use RawConfigurationTrait;

    /**
     * @param array<string, mixed> $raw The complete, untouched "formEditor" configuration array
     * @param int $maximumUndoSteps Number of undo steps the form editor keeps in history
     * @param list<string> $stylesheets Stylesheets loaded in the form editor module
     * @param array<int, string> $translationFiles Translation files keyed by priority
     * @param array<string, mixed> $addInlineSettings Inline settings merged into the page renderer
     * @param array<string, mixed> $dynamicJavaScriptModules JavaScript modules keyed by role (e.g. "app", "viewModel", "additionalViewModelModules")
     * @param array<string, mixed> $formElementGroups Form element groups shown in the "insert element" panel
     * @param array<string, mixed>|null $formEditorFluidConfiguration Fluid paths used to render the inline editor templates
     * @param array<string, mixed>|null $formEditorPartials Mapping of inspector editor identifiers to Fluid partial names
     */
    public function __construct(
        public array $raw = [],
        public int $maximumUndoSteps = 0,
        public array $stylesheets = [],
        public array $translationFiles = [],
        public array $addInlineSettings = [],
        public array $dynamicJavaScriptModules = [],
        public array $formElementGroups = [],
        public ?array $formEditorFluidConfiguration = null,
        public ?array $formEditorPartials = null,
    ) {}

    /**
     * Create the DTO from the raw "formEditor" configuration array.
     *
     * @param array<string, mixed> $configuration
     */
    public static function fromArray(array $configuration): self
    {
        return new self(
            raw: $configuration,
            maximumUndoSteps: (int)($configuration['maximumUndoSteps'] ?? 0),
            stylesheets: self::normalizeStringList($configuration['stylesheets'] ?? null),
            translationFiles: self::normalizeTranslationFiles($configuration['translationFiles'] ?? null),
            addInlineSettings: is_array($configuration['addInlineSettings'] ?? null) ? $configuration['addInlineSettings'] : [],
            dynamicJavaScriptModules: is_array($configuration['dynamicJavaScriptModules'] ?? null) ? $configuration['dynamicJavaScriptModules'] : [],
            formElementGroups: is_array($configuration['formElementGroups'] ?? null) ? $configuration['formElementGroups'] : [],
            formEditorFluidConfiguration: is_array($configuration['formEditorFluidConfiguration'] ?? null) ? $configuration['formEditorFluidConfiguration'] : null,
            formEditorPartials: is_array($configuration['formEditorPartials'] ?? null) ? $configuration['formEditorPartials'] : null,
        );
    }

    /**
     * Return the additional view model JavaScript modules registered by the
     * prototype (loaded in addition to the default view model).
     *
     * @return list<string>
     */
    public function getAdditionalViewModelModules(): array
    {
        return self::normalizeStringList($this->dynamicJavaScriptModules['additionalViewModelModules'] ?? null);
    }

    /**
     * Return the dynamic JavaScript modules reduced to the given role keys
     * (e.g. "app", "mediator", "viewModel"), preserving the role => module map.
     *
     * @param list<string> $roleKeys
     * @return array<string, string>
     */
    public function getJavaScriptModulesForRoles(array $roleKeys): array
    {
        $modules = [];
        foreach ($roleKeys as $roleKey) {
            $module = $this->dynamicJavaScriptModules[$roleKey] ?? null;
            if (is_string($module)) {
                $modules[$roleKey] = $module;
            }
        }
        return $modules;
    }
}
