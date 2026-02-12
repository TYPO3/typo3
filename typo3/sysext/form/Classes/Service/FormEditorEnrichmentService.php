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

namespace TYPO3\CMS\Form\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Service to enrich form editor definitions with additional runtime data.
 *
 * This service processes form editor definitions and enriches them with
 * additional configuration data that is needed for the form editor UI.
 * Currently, it handles the enrichment of textarea editors with RTE options.
 *
 * @internal
 */
#[Autoconfigure(public: true)]
class FormEditorEnrichmentService implements SingletonInterface
{
    public function __construct(
        private readonly RichTextConfigurationService $richTextConfigurationService,
    ) {}

    /**
     * Enrich form editor definitions with RTE options and other runtime data.
     *
     * Processes all form editor definitions and adds CKEditor configuration
     * to textarea editors that have enableRichtext enabled.
     *
     * @param array $formEditorDefinitions The form editor definitions to enrich
     * @return array The enriched form editor definitions
     */
    public function enrichFormEditorDefinitions(array $formEditorDefinitions): array
    {
        // Only enrich with RTE options if the rte_ckeditor extension is loaded
        if (!ExtensionManagementUtility::isLoaded('rte_ckeditor')) {
            return $formEditorDefinitions;
        }

        foreach ($formEditorDefinitions as &$definitions) {
            foreach ($definitions as &$definition) {
                $this->enrichDefinitionWithRichTextOptions($definition);
            }
        }

        return $formEditorDefinitions;
    }

    /**
     * Enrich a single definition with RTE options for its editors and property collections.
     */
    protected function enrichDefinitionWithRichTextOptions(array &$definition): void
    {
        if (isset($definition['editors']) && is_array($definition['editors'])) {
            $this->enrichEditorsWithRichTextOptions($definition['editors']);
        }

        if (isset($definition['propertyCollections']) && is_array($definition['propertyCollections'])) {
            $this->enrichPropertyCollectionsWithRichTextOptions($definition['propertyCollections']);
        }
    }

    /**
     * Enrich property collections (e.g., finishers, validators) with RTE options.
     *
     * Property collections have an additional numeric level in their structure:
     * propertyCollections -> collectionName (e.g., 'finishers') -> numeric index -> editors
     */
    protected function enrichPropertyCollectionsWithRichTextOptions(array &$propertyCollections): void
    {
        foreach ($propertyCollections as &$collectionItems) {
            if (!is_array($collectionItems)) {
                continue;
            }

            foreach ($collectionItems as &$collectionItem) {
                if (isset($collectionItem['editors']) && is_array($collectionItem['editors'])) {
                    $this->enrichEditorsWithRichTextOptions($collectionItem['editors']);
                }
            }
        }
    }

    /**
     * Enrich editors array with RTE options if enableRichtext is set.
     *
     * Iterates through all editors and adds RTE configuration options
     * to textarea editors that have rich text enabled.
     */
    protected function enrichEditorsWithRichTextOptions(array &$editors): void
    {
        foreach ($editors as &$editor) {
            if ($this->shouldEnrichEditorWithRichText($editor)) {
                $editor['rteOptions'] = $this->resolveRichTextOptions($editor);
            }
        }
    }

    /**
     * Check if an editor should be enriched with RTE options.
     *
     * An editor qualifies for RTE enrichment if it is a textarea editor
     * and has the enableRichtext flag set to true.
     */
    protected function shouldEnrichEditorWithRichText(array $editor): bool
    {
        return ($editor['templateName'] ?? '') === 'Inspector-TextareaEditor'
            && ($editor['enableRichtext'] ?? false) === true;
    }

    /**
     * Resolve CKEditor configuration options for the given editor.
     *
     * Retrieves the RTE preset configuration and resolves it into
     * a complete CKEditor configuration that can be used in the form editor.
     */
    protected function resolveRichTextOptions(array $editor): array
    {
        $presetName = $editor['richtextConfiguration'] ?? 'form-label';
        return $this->richTextConfigurationService->resolveCkEditorConfiguration($presetName);
    }
}
