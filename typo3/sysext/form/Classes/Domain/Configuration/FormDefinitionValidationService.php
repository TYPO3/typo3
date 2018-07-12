<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Domain\Configuration;

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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Domain\Configuration\ArrayProcessing\ArrayProcessing;
use TYPO3\CMS\Form\Domain\Configuration\ArrayProcessing\ArrayProcessor;
use TYPO3\CMS\Form\Domain\Configuration\Exception\PropertyException;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Validators\CreatableFormElementPropertiesValidator;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Validators\CreatablePropertyCollectionElementPropertiesValidator;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Validators\FormElementHmacDataValidator;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Validators\PropertyCollectionElementHmacDataValidator;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Validators\ValidationDto;

/**
 * @internal
 */
class FormDefinitionValidationService implements SingletonInterface
{

    /**
     * @var ConfigurationService
     */
    protected $configurationService;

    /**
     * Validate the form definition properties using the form setup.
     * Pseudo workflow:
     * Is the form element type creatable by the form editor?
     *   YES
     *     foreach(form element properties) (without finishers|validators)
     *       is the form element property defined in the form setup (can be manipulated)?
     *         YES
     *           valid!
     *         NO
     *           is the form element property defined in "predefinedDefaults" in the form setup (cannot be manipulated but should be written)?
     *             YES
     *               is the form element property value equals to the value defined in "predefinedDefaults" in the form setup?
     *                 YES
     *                   valid!
     *                 NO
     *                   invalid! throw exception
     *             NO
     *               is there a hmac hash available for the form element property value (cannot be manipulated but should be written)?
     *                 YES
     *                   is the form element property value equals the historical value (and is the historical value valid)?
     *                     YES
     *                       valid!
     *                     NO
     *                       invalid! throw exception
     *                 NO
     *                   invalid! throw exception
     *     foreach(form elements finishers|validators)
     *       is the form elements finisher|validator creatable by the form editor?
     *         YES
     *           foreach(form elements finisher|validator properties)
     *             is the form elements finisher|validator property defined in the form setup (can be manipulated)?
     *               YES
     *                 valid!
     *               NO
     *                 is the form elements finisher|validator property defined in "predefinedDefaults" in the form setup (cannot be manipulated but should be written)?
     *                   YES
     *                     is the form elements finisher|validator property value equals to the value defined in "predefinedDefaults" in the form setup?
     *                       YES
     *                         valid!
     *                       NO
     *                         invalid! throw exception
     *                   NO
     *                     is there a hmac hash available for the form elements finisher|validator property value (can not be manipulated but should be written)?
     *                       YES
     *                         is the form elements finisher|validator property value equals the historical value (and is the historical value valid)?
     *                           YES
     *                             valid!
     *                           NO
     *                             invalid! throw exception
     *                       NO
     *                         invalid! throw exception
     *         NO
     *           foreach(form elements finisher|validator properties)
     *             is there a hmac hash available for the form elements finisher|validator property value (can not be manipulated but should be written)?
     *               YES
     *                 is the form elements finisher|validator property value equals the historical value (and is the historical value valid)?
     *                   YES
     *                     valid!
     *                   NO
     *                     invalid! throw exception
     *               NO
     *                 invalid! throw exception
     *   NO
     *     foreach(form element properties) (without finishers|validators)
     *       is there a hmac hash available for the form element property value (cannot be manipulated but should be written)?
     *         YES
     *           is the form element property value equals the historical value (and is the historical value valid)?
     *             YES
     *               valid!
     *             NO
     *               invalid! throw exception
     *         NO
     *           invalid! throw exception
     *     foreach(form elements finisher|validator properties)
     *       is there a hmac hash available for the form elements finisher|validator property value (can not be manipulated but should be written)?
     *         YES
     *           is the form elements finisher|validator property value equals the historical value (and is the historical value valid)?
     *             YES
     *               valid!
     *             NO
     *               invalid! throw exception
     *         NO
     *           invalid! throw exception
     *
     * @param array $currentFormElement
     * @param string $prototypeName
     * @param string $sessionToken
     * @throws PropertyException
     */
    public function validateFormDefinitionProperties(
        array $currentFormElement,
        string $prototypeName,
        string $sessionToken
    ): void {
        $renderables = $currentFormElement['renderables'] ?? [];
        $propertyCollectionElements = $currentFormElement['finishers'] ?? $currentFormElement['validators'] ?? [];
        $propertyCollectionName = $currentFormElement['type'] === 'Form' ? 'finishers' : 'validators';
        unset($currentFormElement['renderables'], $currentFormElement['finishers'], $currentFormElement['validators']);

        $validationDto = GeneralUtility::makeInstance(
            ValidationDto::class,
            $prototypeName,
            $currentFormElement['type'],
            $currentFormElement['identifier'],
            null,
            $propertyCollectionName
        );

        if ($this->getConfigurationService()->isFormElementTypeCreatableByFormEditor($validationDto)) {
            $this->validateAllPropertyValuesFromCreatableFormElement(
                $currentFormElement,
                $sessionToken,
                $validationDto
            );

            foreach ($propertyCollectionElements as $propertyCollectionElement) {
                $validationDto = $validationDto->withPropertyCollectionElementIdentifier(
                    $propertyCollectionElement['identifier']
                );

                if ($this->getConfigurationService()->isPropertyCollectionElementIdentifierCreatableByFormEditor($validationDto)) {
                    $this->validateAllPropertyValuesFromCreatablePropertyCollectionElement(
                        $propertyCollectionElement,
                        $sessionToken,
                        $validationDto
                    );
                } else {
                    $this->validateAllPropertyCollectionElementValuesByHmac(
                        $propertyCollectionElement,
                        $sessionToken,
                        $validationDto
                    );
                }
            }
        } else {
            $this->validateAllFormElementPropertyValuesByHmac($currentFormElement, $sessionToken, $validationDto);

            foreach ($propertyCollectionElements as $propertyCollectionElement) {
                $this->validateAllPropertyCollectionElementValuesByHmac(
                    $propertyCollectionElement,
                    $sessionToken,
                    $validationDto
                );
            }
        }

        foreach ($renderables as $renderable) {
            $this->validateFormDefinitionProperties($renderable, $prototypeName, $sessionToken);
        }
    }

    /**
     * Returns TRUE if a property value is equals to the historical value
     * and FALSE if not.
     * "Historical values" means values which are available within the form definition
     * while the form editor is loaded and the values which are available after a
     * successful validation of the form definition on a save operation.
     * The value must be equal to the historical value if the property key for the value
     * is not defined within the form setup.
     * This means that the property can not be changed by the form editor but we want to keep the value
     * in its original state.
     * If this is not the case (return value is FALSE), an exception must be thrown.
     *
     * @param array $hmacContent
     * @param mixed $propertyValue
     * @param array $hmacData
     * @param string $sessionToken
     * @return bool
     * @throws PropertyException
     */
    public function isPropertyValueEqualToHistoricalValue(
        array $hmacContent,
        $propertyValue,
        array $hmacData,
        string $sessionToken
    ): bool {
        $this->checkHmacDataIntegrity($hmacData, $hmacContent, $sessionToken);
        $hmacContent[] = $propertyValue;

        $expectedHash = GeneralUtility::hmac(serialize($hmacContent), $sessionToken);
        return hash_equals($expectedHash, $hmacData['hmac']);
    }

    /**
     * Compares the historical value and the hmac hash to ensure the integrity
     * of the data.
     * An exception will be thrown if the value is modified.
     *
     * @param array $hmacData
     * @param array $hmacContent
     * @param string $sessionToken
     * @throws PropertyException
     */
    protected function checkHmacDataIntegrity(array $hmacData, array $hmacContent, string $sessionToken)
    {
        $hmac = $hmacData['hmac'] ?? null;
        if (empty($hmac)) {
            throw new PropertyException('Hmac must not be empty. #1528538222', 1528538222);
        }

        $hmacContent[] = $hmacData['value'] ?? '';
        $expectedHash = GeneralUtility::hmac(serialize($hmacContent), $sessionToken);

        if (!hash_equals($expectedHash, $hmac)) {
            throw new PropertyException('Unauthorized modification of historical data. #1528538252', 1528538252);
        }
    }

    /**
     * Walk through all form element properties and checks
     * if the values matches to their hmac hashes.
     *
     * @param array $currentElement
     * @param string $sessionToken
     * @param ValidationDto $validationDto
     */
    protected function validateAllFormElementPropertyValuesByHmac(
        array $currentElement,
        $sessionToken,
        ValidationDto $validationDto
    ): void {
        GeneralUtility::makeInstance(ArrayProcessor::class, $currentElement)->forEach(
            GeneralUtility::makeInstance(
                ArrayProcessing::class,
                'validateProperties',
                '^(?!(_orig_.*|.*\._orig_.*)$).*',
                GeneralUtility::makeInstance(
                    FormElementHmacDataValidator::class,
                    $currentElement,
                    $sessionToken,
                    $validationDto
                )
            )
        );
    }

    /**
     * Walk through all property collection properties and checks
     * if the values matches to their hmac hashes.
     *
     * @param array $currentElement
     * @param string $sessionToken
     * @param ValidationDto $validationDto
     */
    protected function validateAllPropertyCollectionElementValuesByHmac(
        array $currentElement,
        $sessionToken,
        ValidationDto $validationDto
    ): void {
        GeneralUtility::makeInstance(ArrayProcessor::class, $currentElement)->forEach(
            GeneralUtility::makeInstance(
                ArrayProcessing::class,
                'validateProperties',
                '^(?!(_orig_.*|.*\._orig_.*)$).*',
                GeneralUtility::makeInstance(
                    PropertyCollectionElementHmacDataValidator::class,
                    $currentElement,
                    $sessionToken,
                    $validationDto
                )
            )
        );
    }

    /**
     * Walk through all form element properties and checks
     * if the property is defined within the form editor setup
     * or if the property is definied within the "predefinedDefaults" in the form editor setup
     * and the property value matches the predefined value
     * or if there is a valid hmac hash for the value.
     *
     * @param array $currentElement
     * @param string $sessionToken
     * @param ValidationDto $validationDto
     */
    protected function validateAllPropertyValuesFromCreatableFormElement(
        array $currentElement,
        $sessionToken,
        ValidationDto $validationDto
    ): void {
        GeneralUtility::makeInstance(ArrayProcessor::class, $currentElement)->forEach(
            GeneralUtility::makeInstance(
                ArrayProcessing::class,
                'validateProperties',
                '^(?!(_orig_.*|.*\._orig_.*|type|identifier)$).*',
                GeneralUtility::makeInstance(
                    CreatableFormElementPropertiesValidator::class,
                    $currentElement,
                    $sessionToken,
                    $validationDto
                )
            )
        );
    }

    /**
     * Walk through all property collection properties and checks
     * if the property is defined within the form editor setup
     * or if the property is definied within the "predefinedDefaults" in the form editor setup
     * and the property value matches the predefined value
     * or if there is a valid hmac hash for the value.
     *
     * @param array $currentElement
     * @param string $sessionToken
     * @param ValidationDto $validationDto
     */
    protected function validateAllPropertyValuesFromCreatablePropertyCollectionElement(
        array $currentElement,
        $sessionToken,
        ValidationDto $validationDto
    ): void {
        GeneralUtility::makeInstance(ArrayProcessor::class, $currentElement)->forEach(
            GeneralUtility::makeInstance(
                ArrayProcessing::class,
                'validateProperties',
                '^(?!(_orig_.*|.*\._orig_.*|identifier)$).*',
                GeneralUtility::makeInstance(
                    CreatablePropertyCollectionElementPropertiesValidator::class,
                    $currentElement,
                    $sessionToken,
                    $validationDto
                )
            )
        );
    }

    /**
     * @return ConfigurationService
     */
    protected function getConfigurationService(): ConfigurationService
    {
        if (!($this->configurationService instanceof ConfigurationService)) {
            $this->configurationService = $this->getObjectManager()->get(ConfigurationService::class);
        }
        return $this->configurationService;
    }

    /**
     * @return ObjectManager
     */
    protected function getObjectManager(): ObjectManager
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }
}
