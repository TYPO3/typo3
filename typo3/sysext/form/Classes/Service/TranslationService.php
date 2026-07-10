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

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Domain\Translation\FormTranslationKeychainBuilder;

/**
 * Advanced translations
 * This class is subjected to change.
 * **Do NOT subclass**
 *
 * Scope: frontend / backend
 * @internal
 */
#[Autoconfigure(public: true)]
class TranslationService
{
    public function __construct(
        protected readonly LanguageServiceFactory $languageServiceFactory,
        protected readonly Locales $locales,
        protected readonly FormTranslationKeychainBuilder $keychainBuilder,
    ) {}

    /**
     * Returns the localized label of the LOCAL_LANG key, $key.
     *
     * @param mixed $key The key from the LOCAL_LANG array for which to return the value.
     * @param array|null $arguments the arguments of the extension, being passed over to vsprintf
     * @param mixed $defaultValue
     * @return mixed The value from LOCAL_LANG or $defaultValue if no translation was found.
     * @internal
     */
    public function translate(
        $key,
        ?array $arguments = null,
        ?string $locallangPathAndFilename = null,
        Locale|string|null $locale = null,
        $defaultValue = ''
    ) {
        $key = (string)$key;

        if ($locallangPathAndFilename) {
            $key = $locallangPathAndFilename . ':' . $key;
        }

        // Parse the key to extract file reference and label key separately, which is
        // required for TypoScript label overrides that are keyed by the file reference.
        $keyParts = explode(':', $key);
        if (str_starts_with($key, 'LLL:')) {
            $locallangPathAndFilename = $keyParts[1] . ':' . $keyParts[2];
            $key = $keyParts[3];
        } elseif (PathUtility::isExtensionPath($key)) {
            $locallangPathAndFilename = $keyParts[0] . ':' . $keyParts[1];
            $key = $keyParts[2];
        } elseif (count($keyParts) === 2) {
            $locallangPathAndFilename = $keyParts[0];
            $key = $keyParts[1];
        }

        $request = $this->getRequest();
        $languageService = $this->createLanguageService($locale, $request);

        if (!empty($locallangPathAndFilename) && $request) {
            $this->applyTypoScriptOverrides($languageService, $locallangPathAndFilename, $request);
        }

        $fullReference = !empty($locallangPathAndFilename) ? $locallangPathAndFilename . ':' . $key : $key;
        $value = $languageService->label($fullReference, $arguments ?? []);
        return $value ?? $defaultValue;
    }

    /**
     * Recursively translate values.
     *
     * @return array the modified array
     * @internal
     */
    public function translateValuesRecursive(array $array, array $translationFiles = []): array
    {
        $result = $array;
        foreach ($result as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->translateValuesRecursive($value, $translationFiles);
            } else {
                $translationFiles = $this->sortArrayWithIntegerKeysDescending($translationFiles);

                if (!empty($translationFiles)) {
                    foreach ($translationFiles as $translationFile) {
                        $translatedValue = $this->translate($value, null, $translationFile, null);
                        if (!empty($translatedValue)) {
                            $result[$key] = $translatedValue;
                            break;
                        }
                    }
                } else {
                    $result[$key] = $this->translate($value, null, null, null, $value);
                }
            }
        }
        return $result;
    }

    /**
     * @return array the modified array
     * @internal
     */
    public function translateToAllBackendLanguages(
        string $key,
        ?array $arguments = null,
        array $translationFiles = []
    ): array {
        $result = [];
        $translationFiles = $this->sortArrayWithIntegerKeysDescending($translationFiles);

        foreach ($this->locales->getActiveLanguages() as $language) {
            $result[$language] = $key;
            foreach ($translationFiles as $translationFile) {
                $translatedValue = $this->translate($key, $arguments, $translationFile, $language, $key);
                if ($translatedValue !== $key) {
                    $result[$language] = $translatedValue;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function translateFinisherOption(
        FormRuntime $formRuntime,
        string $finisherIdentifier,
        string $optionKey,
        string $optionValue,
        array $renderingOptions = []
    ): string {
        if (empty($finisherIdentifier)) {
            throw new \InvalidArgumentException('The argument "finisherIdentifier" is empty', 1476216059);
        }
        if (empty($optionKey)) {
            throw new \InvalidArgumentException('The argument "optionKey" is empty', 1476216060);
        }

        if (in_array($optionKey, $renderingOptions['propertiesExcludedFromTranslation'] ?? [], true)) {
            return $optionValue;
        }

        $finisherIdentifier = preg_replace('/Finisher$/', '', $finisherIdentifier);
        $translationFiles = $renderingOptions['translationFiles'] ?? [];
        if (empty($translationFiles)) {
            $translationFiles = $formRuntime->getRenderingOptions()['translation']['translationFiles'];
        }

        $translationFiles = $this->sortArrayWithIntegerKeysDescending($translationFiles);

        if (isset($renderingOptions['translatePropertyValueIfEmpty'])) {
            $translatePropertyValueIfEmpty = (bool)$renderingOptions['translatePropertyValueIfEmpty'];
        } else {
            $translatePropertyValueIfEmpty = true;
        }

        if (empty($optionValue) && !$translatePropertyValueIfEmpty) {
            return $optionValue;
        }

        $locale = null;
        if (isset($renderingOptions['language'])) {
            $locale = $renderingOptions['language'];
        }

        try {
            $arguments = ArrayUtility::getValueByPath($renderingOptions['arguments'] ?? [], $optionKey, '.');
        } catch (MissingArrayPathException $e) {
            $arguments = [];
        }

        $originalFormIdentifier = null;
        if (isset($formRuntime->getRenderingOptions()['_originalIdentifier'])) {
            $originalFormIdentifier = $formRuntime->getRenderingOptions()['_originalIdentifier'];
        }

        $translationKeyChain = $this->keychainBuilder->buildForFinisherOption(
            $translationFiles,
            $formRuntime->getIdentifier(),
            $finisherIdentifier,
            $optionKey,
            $originalFormIdentifier
        );

        $translatedValue = $this->processTranslationChain($translationKeyChain, $locale, $arguments);
        $translatedValue = $this->isEmptyTranslatedValue($translatedValue) ? $optionValue : $translatedValue;

        return $translatedValue;
    }

    /**
     * @throws \InvalidArgumentException
     * @internal
     */
    public function translateFormElementValue(
        RootRenderableInterface $element,
        array $propertyParts,
        FormRuntime $formRuntime,
        Locale|string|null $locale = null,
    ): array|string|null {
        if (empty($propertyParts)) {
            throw new \InvalidArgumentException('The argument "propertyParts" is empty', 1476216007);
        }

        $propertyType = 'properties';
        $property = implode('.', $propertyParts);
        $renderingOptions = $element->getRenderingOptions();

        if ($property === 'label') {
            $defaultValue = $element->getLabel();
        } elseif ($property === 'defaultValue' && $element instanceof FormElementInterface) {
            $defaultValue = $element->getDefaultValue();
        } else {
            if ($element instanceof FormElementInterface) {
                try {
                    $defaultValue = ArrayUtility::getValueByPath($element->getProperties(), $propertyParts, '.');
                } catch (MissingArrayPathException $exception) {
                    $defaultValue = null;
                }
            } else {
                $propertyType = 'renderingOptions';
                try {
                    $defaultValue = ArrayUtility::getValueByPath($renderingOptions, $propertyParts, '.');
                } catch (MissingArrayPathException $exception) {
                    $defaultValue = null;
                }
            }
        }

        if (isset($renderingOptions['translation']['translatePropertyValueIfEmpty'])) {
            $translatePropertyValueIfEmpty = $renderingOptions['translation']['translatePropertyValueIfEmpty'];
        } else {
            $translatePropertyValueIfEmpty = true;
        }

        if ($this->isEmptyTranslatedValue($defaultValue) && !$translatePropertyValueIfEmpty) {
            return $defaultValue;
        }

        $defaultValue = $this->isEmptyTranslatedValue($defaultValue) ? '' : $defaultValue;
        $translationFiles = $renderingOptions['translation']['translationFiles'] ?? [];
        if (empty($translationFiles)) {
            $translationFiles = $formRuntime->getRenderingOptions()['translation']['translationFiles'];
        }

        $translationFiles = $this->sortArrayWithIntegerKeysDescending($translationFiles);

        if (!$locale && isset($renderingOptions['translation']['language'])) {
            $locale = $renderingOptions['translation']['language'];
        }

        try {
            $arguments = ArrayUtility::getValueByPath($renderingOptions['translation']['arguments'] ?? [], $propertyParts, '.');
        } catch (MissingArrayPathException $e) {
            $arguments = [];
        }

        $originalFormIdentifier = null;
        if (isset($formRuntime->getRenderingOptions()['_originalIdentifier'])) {
            $originalFormIdentifier = $formRuntime->getRenderingOptions()['_originalIdentifier'];
        }

        $elementIsFormRuntime = $element instanceof FormRuntime;
        $elementIdentifier = $element->getIdentifier();
        $elementType = $element->getType();

        if ($property === 'options' && is_array($defaultValue)) {
            foreach ($defaultValue as $optionValue => &$optionLabel) {
                if ($elementIsFormRuntime) {
                    $translationKeyChain = $this->keychainBuilder->buildForFormRuntimeOption(
                        $translationFiles,
                        $formRuntime->getIdentifier(),
                        $elementIdentifier,
                        $elementType,
                        $propertyType,
                        $property,
                        $optionValue,
                        $originalFormIdentifier
                    );
                } else {
                    $translationKeyChain = $this->keychainBuilder->buildForElementOption(
                        $translationFiles,
                        $formRuntime->getIdentifier(),
                        $elementIdentifier,
                        $elementType,
                        $propertyType,
                        $property,
                        $optionValue,
                        $originalFormIdentifier
                    );
                }

                $translatedValue = $this->processTranslationChain($translationKeyChain, $locale, $arguments);
                $optionLabel = $this->isEmptyTranslatedValue($translatedValue) ? $optionLabel : $translatedValue;
            }
            $translatedValue = $defaultValue;
        } elseif ($property === 'fluidAdditionalAttributes') {
            // "fluidAdditionalAttributes" is a globally available property and is used across all built-in
            // form templates. However, it's not necessarily defined in the form configuration. This can lead to
            // an empty string as default value, which is invalid. This check makes sure that an array is returned
            // even if the property is not defined.
            if (!is_array($defaultValue)) {
                $defaultValue = [];
            }
            foreach ($defaultValue as $propertyName => &$propertyValue) {
                if ($elementIsFormRuntime) {
                    $translationKeyChain = $this->keychainBuilder->buildForFormRuntimeProperty(
                        $translationFiles,
                        $formRuntime->getIdentifier(),
                        $elementIdentifier,
                        $elementType,
                        $propertyType,
                        $propertyName,
                        $originalFormIdentifier
                    );
                } else {
                    $translationKeyChain = $this->keychainBuilder->buildForElementProperty(
                        $translationFiles,
                        $formRuntime->getIdentifier(),
                        $elementIdentifier,
                        $elementType,
                        $propertyType,
                        $propertyName,
                        $originalFormIdentifier
                    );
                }

                $translatedValue = $this->processTranslationChain($translationKeyChain, $locale, $arguments);
                $propertyValue = $this->isEmptyTranslatedValue($translatedValue) ? $propertyValue : $translatedValue;
            }
            $translatedValue = $defaultValue;
        } else {
            if ($elementIsFormRuntime) {
                $translationKeyChain = $this->keychainBuilder->buildForFormRuntimeProperty(
                    $translationFiles,
                    $formRuntime->getIdentifier(),
                    $elementIdentifier,
                    $elementType,
                    $propertyType,
                    $property,
                    $originalFormIdentifier
                );
            } else {
                $translationKeyChain = $this->keychainBuilder->buildForElementProperty(
                    $translationFiles,
                    $formRuntime->getIdentifier(),
                    $elementIdentifier,
                    $elementType,
                    $propertyType,
                    $property,
                    $originalFormIdentifier
                );
            }

            $translatedValue = $this->processTranslationChain($translationKeyChain, $locale, $arguments);
            $translatedValue = $this->isEmptyTranslatedValue($translatedValue) ? $defaultValue : $translatedValue;
        }

        return $translatedValue;
    }

    /**
     * @throws \InvalidArgumentException
     * @internal
     */
    public function translateFormElementError(
        RootRenderableInterface $element,
        int $code,
        array $arguments,
        string $defaultValue,
        FormRuntime $formRuntime
    ): string {
        if (empty($code)) {
            throw new \InvalidArgumentException('The argument "code" is empty', 1489272978);
        }

        if ($element instanceof FormElementInterface) {
            $validationErrors = $element->getProperties()['validationErrorMessages'] ?? null;
            if (is_array($validationErrors)) {
                foreach ($validationErrors as $validationError) {
                    if ((int)$validationError['code'] === $code) {
                        return sprintf($validationError['message'], ...$arguments);
                    }
                }
            }
        }

        $renderingOptions = $element->getRenderingOptions();
        $translationFiles = $renderingOptions['translation']['translationFiles'] ?? [];
        if (empty($translationFiles)) {
            $translationFiles = $formRuntime->getRenderingOptions()['translation']['translationFiles'];
        }

        $translationFiles = $this->sortArrayWithIntegerKeysDescending($translationFiles);

        $locale = null;
        if (isset($renderingOptions['language'])) {
            $locale = $renderingOptions['language'];
        }

        $originalFormIdentifier = null;
        if (isset($formRuntime->getRenderingOptions()['_originalIdentifier'])) {
            $originalFormIdentifier = $formRuntime->getRenderingOptions()['_originalIdentifier'];
        }

        if ($element instanceof FormRuntime) {
            $translationKeyChain = $this->keychainBuilder->buildForFormRuntimeValidationError(
                $translationFiles,
                $formRuntime->getIdentifier(),
                $element->getIdentifier(),
                $code,
                $originalFormIdentifier
            );
        } else {
            $translationKeyChain = $this->keychainBuilder->buildForValidationError(
                $translationFiles,
                $formRuntime->getIdentifier(),
                $element->getIdentifier(),
                $code,
                $originalFormIdentifier
            );
        }

        $translatedValue = $this->processTranslationChain($translationKeyChain, $locale, $arguments);
        $translatedValue = $this->isEmptyTranslatedValue($translatedValue) ? $defaultValue : $translatedValue;
        return $translatedValue;
    }

    /**
     * @return string|\Stringable|null
     */
    protected function processTranslationChain(
        array $translationKeyChain,
        Locale|string|null $locale = null,
        ?array $arguments = null
    ) {
        $request = $this->getRequest();
        $languageService = $this->createLanguageService($locale, $request);
        $appliedOverridesForFiles = [];

        foreach ($translationKeyChain as $translationKey) {
            if ($request) {
                $fileRef = $this->extractFileReferenceFromKey($translationKey);
                if ($fileRef !== '' && !isset($appliedOverridesForFiles[$fileRef])) {
                    $this->applyTypoScriptOverrides($languageService, $fileRef, $request);
                    $appliedOverridesForFiles[$fileRef] = true;
                }
            }
            $translatedValue = $languageService->label($translationKey, $arguments ?? []);
            if (!$this->isEmptyTranslatedValue($translatedValue)) {
                return $translatedValue;
            }
        }
        return null;
    }

    /**
     * If the array contains numerical keys only, sort it in descending order
     */
    protected function sortArrayWithIntegerKeysDescending(array $array): array
    {
        if (count(array_filter(array_keys($array), 'is_string')) === 0) {
            krsort($array);
        }
        return $array;
    }

    /**
     * Check if given translated value is considered "empty".
     *
     * A translated value is considered "empty" if it's either NULL or
     * an empty string. This helper method exists to perform a less strict
     * check than the native {@see empty()} function, because it is too
     * strict in terms of supported translated values. For example, the
     * value "0" is valid, whereas {@see empty()} would handle it as "empty"
     * and therefore invalid.
     */
    protected function isEmptyTranslatedValue(mixed $translatedValue): bool
    {
        if ($translatedValue === null) {
            return true;
        }

        if (is_string($translatedValue)) {
            return trim($translatedValue) === '';
        }

        if (is_bool($translatedValue)) {
            return !$translatedValue;
        }

        if (is_array($translatedValue)) {
            return $translatedValue === [];
        }

        return false;
    }

    /**
     * Creates a LanguageService for the given locale or the locale from the current request.
     * Returns a LanguageService (which implements TranslatorInterface) rather than the interface
     * directly, since TypoScript label overrides require LanguageService-specific methods.
     */
    private function createLanguageService(Locale|string|null $locale, ?ServerRequestInterface $request): LanguageService
    {
        if ($locale) {
            return $this->languageServiceFactory->create($locale);
        }
        return $this->languageServiceFactory->create($this->locales->createLocaleFromRequest($request));
    }

    /**
     * Applies TypoScript label overrides (plugin.tx_form._LOCAL_LANG) to the given language
     * service for the specified file reference, if a frontend TypoScript setup is present.
     */
    private function applyTypoScriptOverrides(LanguageService $languageService, string $fileRef, ServerRequestInterface $request): void
    {
        $typoScript = $request->getAttribute('frontend.typoscript');
        if ($typoScript instanceof FrontendTypoScript && $typoScript->hasSetup()) {
            $overrideLabels = $languageService->loadTypoScriptLabelsFromExtension('form', $typoScript);
            if ($overrideLabels !== []) {
                $languageService->overrideLabels($fileRef, $overrideLabels);
            }
        }
    }

    /**
     * Extracts the file reference (domain) part from a full translation key reference such as
     * 'EXT:my_ext/path/file.xlf:my.key' or 'LLL:EXT:my_ext/path/file.xlf:my.key'.
     * Returns an empty string when no file reference can be determined.
     */
    private function extractFileReferenceFromKey(string $key): string
    {
        $strippedKey = str_starts_with($key, 'LLL:') ? substr($key, 4) : $key;
        $keyParts = explode(':', $strippedKey);
        if (PathUtility::isExtensionPath($strippedKey)) {
            // e.g. EXT:my_ext/path/file.xlf -> keyParts[0]='EXT', keyParts[1]='my_ext/path/file.xlf'
            return $keyParts[0] . ':' . ($keyParts[1] ?? '');
        }
        // Semantic domain, e.g. 'my.domain:my.key' -> 'my.domain'
        return $keyParts[0];
    }

    private function getRequest(): ?ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'] ?? null;
    }
}
