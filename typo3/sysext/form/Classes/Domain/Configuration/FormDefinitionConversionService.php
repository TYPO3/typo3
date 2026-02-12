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

namespace TYPO3\CMS\Form\Domain\Configuration;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Html\SanitizerBuilderFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Configuration\ArrayProcessing\ArrayProcessing;
use TYPO3\CMS\Form\Domain\Configuration\ArrayProcessing\ArrayProcessor;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Converters\AddHmacDataConverter;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Converters\ConverterDto;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Converters\FinisherTranslationLanguageConverter;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Converters\RemoveHmacDataConverter;
use TYPO3\CMS\Form\Service\RichTextConfigurationService;
use TYPO3\HtmlSanitizer\Sanitizer;

/**
 * @internal
 */
#[Autoconfigure(public: true)]
class FormDefinitionConversionService
{
    public function __construct(
        private readonly RichTextConfigurationService $richTextConfigurationService,
    ) {}

    /**
     * Add a new value "_orig_<propertyName>" for each scalar property value
     * within the form definition as a sibling of the property key.
     * "_orig_<propertyName>" is an array which contains the property value
     * and a hmac hash for the property value.
     * "_orig_<propertyName>" will be used to validate the form definition on saving.
     * @see \TYPO3\CMS\Form\Domain\Configuration\FormDefinitionValidationService::validateFormDefinitionProperties()
     */
    public function addHmacData(array $formDefinition): array
    {
        // Extend the hmac hashing key with a "per form editor session" unique key.
        $sessionToken = $this->generateSessionToken();
        $this->persistSessionToken($sessionToken);

        $converterDto = GeneralUtility::makeInstance(ConverterDto::class, $formDefinition);

        GeneralUtility::makeInstance(ArrayProcessor::class, $formDefinition)->forEach(
            GeneralUtility::makeInstance(
                ArrayProcessing::class,
                'addHmacData',
                '(^identifier$|renderables\.([\d]+)\.identifier$)',
                GeneralUtility::makeInstance(
                    AddHmacDataConverter::class,
                    $converterDto,
                    $sessionToken
                )
            )
        );

        return $converterDto->getFormDefinition();
    }

    /**
     * Remove the "_orig_<propertyName>" values from the form definition.
     */
    public function removeHmacData(array $formDefinition): array
    {
        $converterDto = GeneralUtility::makeInstance(ConverterDto::class, $formDefinition);

        GeneralUtility::makeInstance(ArrayProcessor::class, $formDefinition)->forEach(
            GeneralUtility::makeInstance(
                ArrayProcessing::class,
                'removeHmacData',
                '(_orig_.*|.*\._orig_.*)\.hmac',
                GeneralUtility::makeInstance(
                    RemoveHmacDataConverter::class,
                    $converterDto
                )
            )
        );

        return $converterDto->getFormDefinition();
    }

    /**
     * Migrate various finisher options
     */
    public function migrateFinisherConfiguration(array $formDefinition): array
    {
        $converterDto = GeneralUtility::makeInstance(ConverterDto::class, $formDefinition);

        GeneralUtility::makeInstance(ArrayProcessor::class, $formDefinition)->forEach(
            GeneralUtility::makeInstance(
                ArrayProcessing::class,
                'migrateFinisherLanguageSettings',
                '^finishers\.([\d]+)\.options.translation.language$',
                GeneralUtility::makeInstance(
                    FinisherTranslationLanguageConverter::class,
                    $converterDto
                )
            )
        );

        return $converterDto->getFormDefinition();
    }

    protected function persistSessionToken(string $sessionToken): void
    {
        $this->getBackendUser()->setAndSaveSessionData('extFormProtectionSessionToken', $sessionToken);
    }

    public function sanitizeHtml(array $rawFormDefinitionArray, array $rtePropertyPaths = [], string $defaultBuild = 'default'): array
    {
        return $this->sanitizeValuesRecursively($rawFormDefinitionArray, $rtePropertyPaths, $defaultBuild);
    }

    public function transformRteContentForPersistence(array $formDefinition, array $rtePropertyPaths): array
    {
        if ($rtePropertyPaths === []) {
            return $formDefinition;
        }

        return $this->transformRteContentRecursively($formDefinition, $rtePropertyPaths, $this->richTextConfigurationService, 'persistence');
    }

    public function transformRteContentForRichTextEditor(array $formDefinition, array $rtePropertyPaths): array
    {
        if ($rtePropertyPaths === []) {
            return $formDefinition;
        }

        return $this->transformRteContentRecursively($formDefinition, $rtePropertyPaths, $this->richTextConfigurationService, 'rte');
    }

    protected function transformRteContentRecursively(
        array $formDefinition,
        array $rtePropertyPaths,
        RichTextConfigurationService $richTextConfigurationService,
        string $direction = 'persistence'
    ): array {
        // Get the element type (e.g., 'Checkbox', 'StaticText', 'Form')
        $elementType = $formDefinition['type'] ?? null;

        // Transform properties for this specific element type
        if ($elementType !== null && isset($rtePropertyPaths[$elementType])) {
            foreach ($rtePropertyPaths[$elementType] as $propertyPath => $presetName) {
                $value = $this->getValueByPath($formDefinition, $propertyPath);
                if (is_string($value) && $value !== '') {
                    $transformedValue = $direction === 'persistence'
                        ? $richTextConfigurationService->transformTextForPersistence($value, $presetName)
                        : $richTextConfigurationService->transformTextForRichTextEditor($value, $presetName);
                    $formDefinition = $this->setValueByPath($formDefinition, $propertyPath, $transformedValue);
                }
            }
        }

        // Recurse into renderables (form elements on pages)
        if (isset($formDefinition['renderables']) && is_array($formDefinition['renderables'])) {
            foreach ($formDefinition['renderables'] as $key => $renderable) {
                if (is_array($renderable)) {
                    $formDefinition['renderables'][$key] = $this->transformRteContentRecursively(
                        $renderable,
                        $rtePropertyPaths,
                        $richTextConfigurationService,
                        $direction
                    );
                }
            }
        }

        // Transform finisher options
        if (isset($formDefinition['finishers']) && is_array($formDefinition['finishers'])) {
            $finisherRtePaths = $rtePropertyPaths['_finishers'] ?? [];
            foreach ($formDefinition['finishers'] as $key => $finisher) {
                if (!is_array($finisher)) {
                    continue;
                }

                $finisherIdentifier = $finisher['identifier'] ?? null;
                if ($finisherIdentifier === null || !isset($finisherRtePaths[$finisherIdentifier])) {
                    continue;
                }

                foreach ($finisherRtePaths[$finisherIdentifier] as $propertyPath => $presetName) {
                    // Property path in finisher config is like 'options.message'
                    $value = $this->getValueByPath($finisher, $propertyPath);
                    if (is_string($value) && $value !== '') {
                        $transformedValue = $direction === 'persistence'
                            ? $richTextConfigurationService->transformTextForPersistence($value, $presetName)
                            : $richTextConfigurationService->transformTextForRichTextEditor($value, $presetName);
                        $finisher = $this->setValueByPath($finisher, $propertyPath, $transformedValue);
                        $formDefinition['finishers'][$key] = $finisher;
                    }
                }
            }
        }

        return $formDefinition;
    }

    protected function getValueByPath(array $array, string $path): mixed
    {
        $keys = explode('.', $path);
        $current = $array;

        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return null;
            }
            $current = $current[$key];
        }

        return $current;
    }

    protected function setValueByPath(array $array, string $path, mixed $value): array
    {
        $keys = explode('.', $path);
        $current = &$array;

        foreach ($keys as $i => $key) {
            if ($i === count($keys) - 1) {
                $current[$key] = $value;
            } else {
                if (!isset($current[$key]) || !is_array($current[$key])) {
                    $current[$key] = [];
                }
                $current = &$current[$key];
            }
        }

        return $array;
    }

    /**
     * Extract RTE-enabled property paths from prototype configuration.
     *
     * Scans the form editor configuration for all form element types and finishers
     * to find editors with enableRichtext=true and returns their property paths
     * along with the RTE preset name, organized by element type.
     *
     * @param array $prototypeConfiguration The prototype configuration array
     * @return array Map of element types to their RTE property paths
     *               Format: [
     *                   'Checkbox' => ['label' => 'form-label'],
     *                   'StaticText' => ['properties.text' => 'form-content'],
     *                   '_finishers' => ['Confirmation' => ['options.message' => 'form-label']]
     *               ]
     */
    public function extractRtePropertyPaths(array $prototypeConfiguration): array
    {
        $rtePropertyPaths = [];

        // Extract from form elements definition
        $formElementsDefinition = $prototypeConfiguration['formElementsDefinition'] ?? [];
        foreach ($formElementsDefinition as $formElementType => $elementConfig) {
            $editors = $elementConfig['formEditor']['editors'] ?? [];
            foreach ($editors as $editor) {
                if ($this->isRteEditor($editor)) {
                    $propertyPath = $editor['propertyPath'] ?? '';
                    $presetName = $editor['richtextConfiguration'] ?? 'form-label';
                    if ($propertyPath !== '') {
                        $rtePropertyPaths[$formElementType][$propertyPath] = $presetName;
                    }
                }
            }
        }

        // Extract from finishers definition (global finisher definitions)
        $finishersDefinition = $prototypeConfiguration['finishersDefinition'] ?? [];
        foreach ($finishersDefinition as $finisherIdentifier => $finisherConfig) {
            $editors = $finisherConfig['formEditor']['editors'] ?? [];
            foreach ($editors as $editor) {
                if ($this->isRteEditor($editor)) {
                    $propertyPath = $editor['propertyPath'] ?? '';
                    $presetName = $editor['richtextConfiguration'] ?? 'form-label';
                    if ($propertyPath !== '') {
                        $rtePropertyPaths['_finishers'][$finisherIdentifier][$propertyPath] = $presetName;
                    }
                }
            }
        }

        return $rtePropertyPaths;
    }

    /**
     * Check if an editor configuration represents an RTE-enabled textarea.
     */
    protected function isRteEditor(array $editor): bool
    {
        return ($editor['templateName'] ?? '') === 'Inspector-TextareaEditor'
            && ($editor['enableRichtext'] ?? false) === true;
    }

    /**
     * Recursively sanitizes values in form definition.
     *
     * For RTE-enabled fields: Uses HtmlSanitizer with the preset configured in the RTE configuration
     * For all other string fields: Uses strip_tags to remove ALL HTML
     *
     * @param array $array The array to sanitize
     * @param array $rtePropertyPaths Map of element types to their RTE property paths with preset names
     * @param string $defaultBuild Default sanitizer build name for RTE fields without specific preset
     * @param string|null $currentElementType The current element type being processed
     * @param string $currentPath The current property path being processed
     */
    protected function sanitizeValuesRecursively(
        array $array,
        array $rtePropertyPaths = [],
        string $defaultBuild = 'default',
        ?string $currentElementType = null,
        string $currentPath = ''
    ): array {
        $result = $array;

        // Detect element type from current array (only at element root level)
        $elementType = $result['type'] ?? $currentElementType;

        // Get RTE property paths for this element type (with their preset names)
        $elementRtePaths = [];
        if ($elementType !== null && isset($rtePropertyPaths[$elementType])) {
            $elementRtePaths = $rtePropertyPaths[$elementType];
        }

        foreach ($result as $key => $value) {
            // Build the full property path
            $propertyPath = $currentPath === '' ? $key : $currentPath . '.' . $key;

            if ($key === 'renderables' && is_array($value)) {
                // For renderables, process each child element with fresh context
                foreach ($value as $childKey => $childValue) {
                    if (is_array($childValue)) {
                        $result[$key][$childKey] = $this->sanitizeValuesRecursively(
                            $childValue,
                            $rtePropertyPaths,
                            $defaultBuild
                        );
                    }
                }
            } elseif ($key === 'finishers' && is_array($value)) {
                // Handle finishers separately
                $finisherRtePaths = $rtePropertyPaths['_finishers'] ?? [];
                foreach ($value as $finisherKey => $finisher) {
                    if (is_array($finisher)) {
                        $finisherIdentifier = $finisher['identifier'] ?? null;
                        $finisherRteFields = [];
                        if ($finisherIdentifier !== null && isset($finisherRtePaths[$finisherIdentifier])) {
                            $finisherRteFields = $finisherRtePaths[$finisherIdentifier];
                        }
                        $result[$key][$finisherKey] = $this->sanitizeFinisherRecursively(
                            $finisher,
                            $finisherRteFields,
                            $defaultBuild
                        );
                    }
                }
            } elseif (is_array($value)) {
                // Recurse into nested arrays, keeping the element type and building path
                $result[$key] = $this->sanitizeValuesRecursively(
                    $value,
                    $rtePropertyPaths,
                    $defaultBuild,
                    $elementType,
                    $propertyPath
                );
            } elseif (is_string($value) || (is_object($value) && method_exists($value, '__toString'))) {
                $stringValue = (string)$value;

                // Check if this property path is an RTE field for the current element type
                if (isset($elementRtePaths[$propertyPath])) {
                    // RTE field: use HtmlSanitizer with the configured preset
                    // This ensures sanitization even for form definitions from external sources
                    $presetBuild = $this->resolveSanitizerBuildFromPreset($elementRtePaths[$propertyPath]);
                    $result[$key] = $this->sanitizeWithBuild($stringValue, $presetBuild ?? $defaultBuild);
                } else {
                    // Non-RTE field: strip ALL HTML tags for security
                    $result[$key] = strip_tags($stringValue);
                }
            }
        }

        return $result;
    }

    /**
     * Recursively sanitize finisher values.
     *
     * @param array $finisher The finisher configuration
     * @param array $rteFields Map of RTE field paths to their preset names
     * @param string $defaultBuild Default sanitizer build name
     * @param string $currentPath Current property path
     */
    protected function sanitizeFinisherRecursively(
        array $finisher,
        array $rteFields,
        string $defaultBuild = 'default',
        string $currentPath = ''
    ): array {
        foreach ($finisher as $key => $value) {
            $fullPath = $currentPath === '' ? $key : $currentPath . '.' . $key;

            if (is_array($value)) {
                $finisher[$key] = $this->sanitizeFinisherRecursively($value, $rteFields, $defaultBuild, $fullPath);
            } elseif (is_string($value) || (is_object($value) && method_exists($value, '__toString'))) {
                $stringValue = (string)$value;

                if (isset($rteFields[$fullPath])) {
                    // RTE field: use HtmlSanitizer with the configured preset
                    $presetBuild = $this->resolveSanitizerBuildFromPreset($rteFields[$fullPath]);
                    $finisher[$key] = $this->sanitizeWithBuild($stringValue, $presetBuild ?? $defaultBuild);
                } else {
                    // Non-RTE field: strip ALL HTML tags for security
                    $finisher[$key] = strip_tags($stringValue);
                }
            }
        }

        return $finisher;
    }

    /**
     * Resolve the sanitizer build name from an RTE preset configuration.
     *
     * @param string $presetName The RTE preset name (e.g., 'form-label', 'form-content')
     * @return string|null The sanitizer build name, or null if not configured
     */
    protected function resolveSanitizerBuildFromPreset(string $presetName): ?string
    {
        $processingConfig = $this->richTextConfigurationService->resolveProcessingConfiguration($presetName);
        return $processingConfig['HTMLparser_db.']['htmlSanitize.']['build'] ?? null;
    }

    /**
     * Sanitize HTML content with the specified sanitizer build.
     *
     * @param string $content The HTML content to sanitize
     * @param string $build The sanitizer build name or class name
     * @return string The sanitized content
     */
    protected function sanitizeWithBuild(string $content, string $build): string
    {
        return $this->createSanitizer($build)->sanitize($content);
    }

    /**
     * Create a sanitizer instance for the given build configuration.
     *
     * Supports both preset names (e.g., 'default') and class names implementing BuilderInterface.
     *
     * @param string $build The sanitizer build name or class name
     * @return Sanitizer The sanitizer instance
     */
    protected function createSanitizer(string $build): Sanitizer
    {
        if (class_exists($build) && is_a($build, \TYPO3\HtmlSanitizer\Builder\BuilderInterface::class, true)) {
            $builder = GeneralUtility::makeInstance($build);
        } else {
            $factory = GeneralUtility::makeInstance(SanitizerBuilderFactory::class);
            $builder = $factory->build($build);
        }
        return $builder->build();
    }

    /**
     * Generates the random token which is used in the hash for the form tokens.
     *
     * @return string
     */
    protected function generateSessionToken(): string
    {
        return GeneralUtility::makeInstance(Random::class)->generateRandomHexString(64);
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
