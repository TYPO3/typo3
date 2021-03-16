<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Validators;

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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Form\Domain\Configuration\Exception\PropertyException;

/**
 * @internal
 */
class CreatablePropertyCollectionElementPropertiesValidator extends CollectionBasedValidator
{

    /**
     * Checks if the property collection element property is defined
     * within the form editor setup or if the property is definied within
     * the "predefinedDefaults" in the form editor setup
     * and the property value matches the predefined value
     * or if there is a valid hmac hash for the value.
     * If the property collection element property is defined within the form editor setup
     * and there is no valid hmac hash for the value
     * and is the form property collection element property configured to only allow a limited set of values,
     * check the current (submitted) value against the allowed set of values (defined within the form setup).
     *
     * @param string $key
     * @param mixed $value
     */
    public function __invoke(string $key, $value)
    {
        $dto = $this->validationDto->withPropertyPath($key);

        if ($this->getConfigurationService()->isPropertyCollectionPropertyDefinedInFormEditorSetup($dto)) {
            if ($this->getConfigurationService()->propertyCollectionPropertyHasLimitedAllowedValuesDefinedWithinFormEditorSetup($dto)) {
                $this->validatePropertyCollectionPropertyValue($value, $dto);
            }
        } elseif (
            $this->getConfigurationService()->isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup($dto)
            && !ArrayUtility::isValidPath($this->currentElement, $this->buildHmacDataPath($dto->getPropertyPath()), '.')
        ) {
            $this->validatePropertyCollectionElementPredefinedDefaultValue($value, $dto);
        } else {
            $this->validatePropertyCollectionElementPropertyValueByHmacData(
                $this->currentElement,
                $value,
                $this->sessionToken,
                $dto
            );
        }
    }

    /**
     * Throws an exception if the value from a property collection property
     * does not match the default value from the form editor setup.
     *
     * @param mixed $value
     * @param ValidationDto $dto
     * @throws PropertyException
     */
    protected function validatePropertyCollectionElementPredefinedDefaultValue(
        $value,
        ValidationDto $dto
    ): void {
        // If the property collection element is newely created, we have to compare the $value (form definition) with $predefinedDefaultValue (form setup)
        // to check the integrity (at this time we don't have a hmac on the value to check the integrity)
        $predefinedDefaultValue = $this->getConfigurationService()->getPropertyCollectionPredefinedDefaultValueFromFormEditorSetup($dto);
        if ($value !== $predefinedDefaultValue) {
            $throwException = true;

            if (is_string($predefinedDefaultValue)) {
                // Last chance:
                // Get all translations (from all backend languages) for the untranslated! $predefinedDefaultValue and
                // compare the (already translated) $value (from the form definition) against the possible
                // translations from $predefinedDefaultValue.
                $untranslatedPredefinedDefaultValue = $this->getConfigurationService()->getPropertyCollectionPredefinedDefaultValueFromFormEditorSetup($dto, false);
                $translations = $this->getConfigurationService()->getAllBackendTranslationsForTranslationKey(
                    $untranslatedPredefinedDefaultValue,
                    $dto->getPrototypeName()
                );

                if (in_array($value, $translations, true)) {
                    $throwException = false;
                }
            }

            if ($throwException) {
                $message = 'The value "%s" of property "%s" (form element "%s" / "%s.%s") is not equal to the default value "%s" #1528591502';
                throw new PropertyException(
                    sprintf(
                        $message,
                        $value,
                        $dto->getPropertyPath(),
                        $dto->getFormElementIdentifier(),
                        $dto->getPropertyCollectionName(),
                        $dto->getPropertyCollectionElementIdentifier(),
                        $predefinedDefaultValue
                    ),
                    1528591502
                );
            }
        }
    }

    /**
     * Throws an exception if the value from a property collection property
     * does not match the allowed set of values (defined within the form setup).
     *
     * @param mixed $value
     * @param ValidationDto $dto
     * @throws PropertyException
     */
    protected function validatePropertyCollectionPropertyValue(
        $value,
        ValidationDto $dto
    ): void {
        $allowedValues = $this->getConfigurationService()->getAllowedValuesForPropertyCollectionPropertyFromFormEditorSetup($dto);

        if (!in_array($value, $allowedValues, true)) {
            $untranslatedAllowedValues = $this->getConfigurationService()->getAllowedValuesForPropertyCollectionPropertyFromFormEditorSetup($dto, false);
            // Compare the $value against the untranslated set of allowed values
            if (in_array($value, $untranslatedAllowedValues, true)) {
                // All good, $value is within the untranslated set of allowed values
                return;
            }
            // Get all translations (from all backend languages) for the untranslated! $allowedValues and
            // compare the (already translated) $value (from the form definition) against all possible
            // translations for $untranslatedAllowedValues.
            $allPossibleAllowedValuesTranslations = $this->getConfigurationService()->getAllBackendTranslationsForTranslationKeys(
                $untranslatedAllowedValues,
                $dto->getPrototypeName()
            );

            foreach ($allPossibleAllowedValuesTranslations as $translations) {
                if (in_array($value, $translations, true)) {
                    // All good, $value is within the set of translated allowed values
                    return;
                }
            }

            // Last chance:
            // If $value is not configured within the form setup as an allowed value
            // but was written within the form definition by hand (and therefore contains a hmac),
            // check if $value is manipulated.
            // If $value has no hmac or if the hmac exists but is not valid,
            // then $this->validatePropertyCollectionElementPropertyValueByHmacData() will
            // throw an exception.
            $this->validatePropertyCollectionElementPropertyValueByHmacData(
                $this->currentElement,
                $value,
                $this->sessionToken,
                $dto
            );
        }
    }
}
