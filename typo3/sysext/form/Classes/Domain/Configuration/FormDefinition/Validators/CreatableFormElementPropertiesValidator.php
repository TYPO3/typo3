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

namespace TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Validators;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Form\Domain\Configuration\Exception\PropertyException;

/**
 * @internal
 */
class CreatableFormElementPropertiesValidator extends ElementBasedValidator
{

    /**
     * Checks if the form element property is defined within the form editor setup
     * or if the property is defined within the "predefinedDefaults" in the form editor setup
     * and the property value matches the predefined value
     * or if there is a valid hmac hash for the value.
     *
     * @param string $key
     * @param mixed $value
     */
    public function __invoke(string $key, $value)
    {
        $dto = $this->validationDto->withPropertyPath($key);

        if (!$this->getConfigurationService()->isFormElementPropertyDefinedInFormEditorSetup($dto)) {
            if (
                $this->getConfigurationService()->isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetup($dto)
                && !ArrayUtility::isValidPath($this->currentElement, $this->buildHmacDataPath($dto->getPropertyPath()), '.')
            ) {
                // If the form element is newly created, we have to compare the $value (form definition) with $predefinedDefaultValue (form setup)
                // to check the integrity (at this time we don't have a hmac for the $value to check the integrity)
                $predefinedDefaultValue = $this->getConfigurationService()->getFormElementPredefinedDefaultValueFromFormEditorSetup($dto);
                if ($value !== $predefinedDefaultValue) {
                    $throwException = true;

                    if (is_string($predefinedDefaultValue)) {
                        // Last chance:
                        // Get all translations (from all backend languages) for the untranslated! $predefinedDefaultValue and
                        // compare the (already translated) $value (from the form definition) against the possible
                        // translations from $predefinedDefaultValue.
                        // Usecase:
                        //   * backend language is EN
                        //   * open the form editor and add a ContentElement form element
                        //   * switch to another browser tab and change the backend language to DE
                        //   * clear the cache
                        //   * go back to the form editor and click the save button
                        // Out of scope:
                        //   * the same scenario as above + delete the previous chosen backend language within the maintenance tool
                        $untranslatedPredefinedDefaultValue = $this->getConfigurationService()->getFormElementPredefinedDefaultValueFromFormEditorSetup($dto, false);
                        $translations = $this->getConfigurationService()->getAllBackendTranslationsForTranslationKey(
                            $untranslatedPredefinedDefaultValue,
                            $dto->getPrototypeName()
                        );

                        if (in_array($value, $translations, true)) {
                            $throwException = false;
                        }
                    }

                    if ($throwException) {
                        $message = 'The value "%s" of property "%s" (form element "%s") is not equal to the default value "%s" #1528588035';
                        throw new PropertyException(
                            sprintf(
                                $message,
                                $value,
                                $dto->getPropertyPath(),
                                $dto->getFormElementIdentifier(),
                                $predefinedDefaultValue
                            ),
                            1528588035
                        );
                    }
                }
            } else {
                $this->validateFormElementPropertyValueByHmacData(
                    $this->currentElement,
                    $value,
                    $this->sessionToken,
                    $dto
                );
            }
        }
    }
}
