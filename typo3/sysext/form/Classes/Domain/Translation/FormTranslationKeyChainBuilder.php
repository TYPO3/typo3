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

namespace TYPO3\CMS\Form\Domain\Translation;

/**
 * Builds ordered translation key chains for form-specific entities.
 *
 * The chain follows a specificity-first order:
 *   1. form-specific + element/finisher-specific
 *   2. form-specific generic
 *   3. element/type-generic
 *
 * @internal
 */
final class FormTranslationKeyChainBuilder
{
    /**
     * Builds the key chain for a scalar property on a regular form element
     * (e.g. "label", "placeholder", any renderingOption).
     *
     * @param string[] $translationFiles
     * @return string[]
     */
    public function buildForElementProperty(
        array $translationFiles,
        string $formIdentifier,
        string $elementIdentifier,
        string $elementType,
        string $propertyType,
        string $property,
        ?string $originalFormIdentifier
    ): array {
        $chain = [];
        foreach ($translationFiles as $translationFile) {
            if ($this->hasOriginalFormIdentifier($originalFormIdentifier)) {
                $chain[] = sprintf('%s:%s.element.%s.%s.%s', $translationFile, $originalFormIdentifier, $elementIdentifier, $propertyType, $property);
            }
            array_push($chain, ...$this->buildElementPropertyKeys($translationFile, $formIdentifier, $elementIdentifier, $elementType, $propertyType, $property));
        }
        return $chain;
    }

    /**
     * Builds the key chain for a scalar property on the FormRuntime itself,
     * where the original form identifier is used as the element segment.
     *
     * @param string[] $translationFiles
     * @return string[]
     */
    public function buildForFormRuntimeProperty(
        array $translationFiles,
        string $formIdentifier,
        string $elementIdentifier,
        string $elementType,
        string $propertyType,
        string $property,
        ?string $originalFormIdentifier
    ): array {
        $chain = [];
        foreach ($translationFiles as $translationFile) {
            if ($this->hasOriginalFormIdentifier($originalFormIdentifier)) {
                $chain[] = sprintf('%s:%s.element.%s.%s.%s', $translationFile, $originalFormIdentifier, $originalFormIdentifier, $propertyType, $property);
                $chain[] = sprintf('%s:element.%s.%s.%s', $translationFile, $originalFormIdentifier, $propertyType, $property);
            }
            array_push($chain, ...$this->buildElementPropertyKeys($translationFile, $formIdentifier, $elementIdentifier, $elementType, $propertyType, $property));
        }
        return $chain;
    }

    /**
     * Builds the key chain for a single option entry inside an "options"
     * array property on a regular element (e.g. Select / RadioButton / Checkbox groups).
     *
     * @param string[] $translationFiles
     * @return string[]
     */
    public function buildForElementOption(
        array $translationFiles,
        string $formIdentifier,
        string $elementIdentifier,
        string $elementType,
        string $propertyType,
        string $property,
        string|int $optionValue,
        ?string $originalFormIdentifier
    ): array {
        $chain = [];
        foreach ($translationFiles as $translationFile) {
            if ($this->hasOriginalFormIdentifier($originalFormIdentifier)) {
                $chain[] = sprintf('%s:%s.element.%s.%s.%s.%s', $translationFile, $originalFormIdentifier, $elementIdentifier, $propertyType, $property, $optionValue);
            }
            array_push($chain, ...$this->buildElementOptionKeys($translationFile, $formIdentifier, $elementIdentifier, $elementType, $propertyType, $property, $optionValue));
        }
        return $chain;
    }

    /**
     * Builds the key chain for a single option entry on the FormRuntime itself,
     * where the original form identifier is used as the element segment.
     *
     * @param string[] $translationFiles
     * @return string[]
     */
    public function buildForFormRuntimeOption(
        array $translationFiles,
        string $formIdentifier,
        string $elementIdentifier,
        string $elementType,
        string $propertyType,
        string $property,
        string|int $optionValue,
        ?string $originalFormIdentifier
    ): array {
        $chain = [];
        foreach ($translationFiles as $translationFile) {
            if ($this->hasOriginalFormIdentifier($originalFormIdentifier)) {
                $chain[] = sprintf('%s:%s.element.%s.%s.%s.%s', $translationFile, $originalFormIdentifier, $originalFormIdentifier, $propertyType, $property, $optionValue);
                $chain[] = sprintf('%s:element.%s.%s.%s.%s', $translationFile, $originalFormIdentifier, $propertyType, $property, $optionValue);
            }
            array_push($chain, ...$this->buildElementOptionKeys($translationFile, $formIdentifier, $elementIdentifier, $elementType, $propertyType, $property, $optionValue));
        }
        return $chain;
    }

    /**
     * Builds the key chain for a validation error code on a regular form element.
     *
     * @param string[] $translationFiles
     * @return string[]
     */
    public function buildForValidationError(
        array $translationFiles,
        string $formIdentifier,
        string $elementIdentifier,
        int $code,
        ?string $originalFormIdentifier
    ): array {
        $chain = [];
        foreach ($translationFiles as $translationFile) {
            if ($this->hasOriginalFormIdentifier($originalFormIdentifier)) {
                $chain[] = sprintf('%s:%s.validation.error.%s.%s', $translationFile, $originalFormIdentifier, $elementIdentifier, $code);
                $chain[] = sprintf('%s:%s.validation.error.%s', $translationFile, $originalFormIdentifier, $code);
            }
            array_push($chain, ...$this->buildValidationErrorKeys($translationFile, $formIdentifier, $elementIdentifier, $code));
        }
        return $chain;
    }

    /**
     * Builds the key chain for a validation error code on the FormRuntime itself,
     * where the original form identifier is used as the element segment.
     *
     * @param string[] $translationFiles
     * @return string[]
     */
    public function buildForFormRuntimeValidationError(
        array $translationFiles,
        string $formIdentifier,
        string $elementIdentifier,
        int $code,
        ?string $originalFormIdentifier
    ): array {
        $chain = [];
        foreach ($translationFiles as $translationFile) {
            if ($this->hasOriginalFormIdentifier($originalFormIdentifier)) {
                $chain[] = sprintf('%s:%s.validation.error.%s.%s', $translationFile, $originalFormIdentifier, $originalFormIdentifier, $code);
                $chain[] = sprintf('%s:validation.error.%s.%s', $translationFile, $originalFormIdentifier, $code);
                $chain[] = sprintf('%s:%s.validation.error.%s', $translationFile, $originalFormIdentifier, $code);
            }
            array_push($chain, ...$this->buildValidationErrorKeys($translationFile, $formIdentifier, $elementIdentifier, $code));
        }
        return $chain;
    }

    /**
     * Builds the key chain for a single finisher option.
     *
     * @param string[] $translationFiles
     * @return string[]
     */
    public function buildForFinisherOption(
        array $translationFiles,
        string $formIdentifier,
        string $finisherIdentifier,
        string $optionKey,
        ?string $originalFormIdentifier
    ): array {
        $chain = [];
        foreach ($translationFiles as $translationFile) {
            if ($this->hasOriginalFormIdentifier($originalFormIdentifier)) {
                $chain[] = sprintf('%s:%s.finisher.%s.%s', $translationFile, $originalFormIdentifier, $finisherIdentifier, $optionKey);
            }
            $chain[] = sprintf('%s:%s.finisher.%s.%s', $translationFile, $formIdentifier, $finisherIdentifier, $optionKey);
            $chain[] = sprintf('%s:finisher.%s.%s', $translationFile, $finisherIdentifier, $optionKey);
        }
        return $chain;
    }

    private function hasOriginalFormIdentifier(?string $originalFormIdentifier): bool
    {
        return $originalFormIdentifier !== null && $originalFormIdentifier !== '';
    }

    /**
     * @return string[]
     */
    private function buildElementPropertyKeys(
        string $translationFile,
        string $formIdentifier,
        string $elementIdentifier,
        string $elementType,
        string $propertyType,
        string $property
    ): array {
        return [
            sprintf('%s:%s.element.%s.%s.%s', $translationFile, $formIdentifier, $elementIdentifier, $propertyType, $property),
            sprintf('%s:element.%s.%s.%s', $translationFile, $elementIdentifier, $propertyType, $property),
            sprintf('%s:element.%s.%s.%s', $translationFile, $elementType, $propertyType, $property),
        ];
    }

    /**
     * @return string[]
     */
    private function buildElementOptionKeys(
        string $translationFile,
        string $formIdentifier,
        string $elementIdentifier,
        string $elementType,
        string $propertyType,
        string $property,
        string|int $optionValue
    ): array {
        return [
            sprintf('%s:%s.element.%s.%s.%s.%s', $translationFile, $formIdentifier, $elementIdentifier, $propertyType, $property, $optionValue),
            sprintf('%s:element.%s.%s.%s.%s', $translationFile, $elementIdentifier, $propertyType, $property, $optionValue),
            sprintf('%s:element.%s.%s.%s.%s', $translationFile, $elementType, $propertyType, $property, $optionValue),
        ];
    }

    /**
     * @return string[]
     */
    private function buildValidationErrorKeys(
        string $translationFile,
        string $formIdentifier,
        string $elementIdentifier,
        int $code
    ): array {
        return [
            sprintf('%s:%s.validation.error.%s.%s', $translationFile, $formIdentifier, $elementIdentifier, $code),
            sprintf('%s:%s.validation.error.%s', $translationFile, $formIdentifier, $code),
            sprintf('%s:validation.error.%s.%s', $translationFile, $elementIdentifier, $code),
            sprintf('%s:validation.error.%s', $translationFile, $code),
        ];
    }
}
