<?php
namespace TYPO3\CMS\Extbase\Mvc\Controller;

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

use TYPO3\CMS\Core\Error\Http\BadRequestException;
use TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForHashGenerationException;
use TYPO3\CMS\Extbase\Security\Exception\InvalidHashException;

/**
 * This is a Service which can generate a request hash and check whether the currently given arguments
 * fit to the request hash.
 *
 * It is used when forms are generated and submitted:
 * After a form has been generated, the method "generateRequestHash" is called with the names of all form fields.
 * It cleans up the array of form fields and creates another representation of it, which is then serialized and hashed.
 *
 * Both serialized form field list and the added hash form the request hash, which will be sent over the wire (as an argument __hmac).
 *
 * On the validation side, the validation happens in two steps:
 * 1) Check if the request hash is consistent (the hash value fits to the serialized string)
 * 2) Check that _all_ GET/POST parameters submitted occur inside the form field list of the request hash.
 *
 * Note: It is crucially important that a private key is computed into the hash value! This is done inside the HashService.
 *
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class MvcPropertyMappingConfigurationService implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * The hash service class to use
     *
     * @var \TYPO3\CMS\Extbase\Security\Cryptography\HashService
     */
    protected $hashService;

    /**
     * @param \TYPO3\CMS\Extbase\Security\Cryptography\HashService $hashService
     */
    public function injectHashService(\TYPO3\CMS\Extbase\Security\Cryptography\HashService $hashService)
    {
        $this->hashService = $hashService;
    }

    /**
     * Generate a request hash for a list of form fields
     *
     * @param array $formFieldNames Array of form fields
     * @param string $fieldNamePrefix
     *
     * @return string trusted properties token
     * @throws \TYPO3\CMS\EXTBASE\Security\Exception\InvalidArgumentForHashGenerationException
     */
    public function generateTrustedPropertiesToken($formFieldNames, $fieldNamePrefix = '')
    {
        $formFieldArray = [];
        foreach ($formFieldNames as $formField) {
            $formFieldParts = explode('[', $formField);
            $currentPosition = &$formFieldArray;
            $formFieldPartsCount = count($formFieldParts);
            for ($i = 0; $i < $formFieldPartsCount; $i++) {
                $formFieldPart = $formFieldParts[$i];
                $formFieldPart = rtrim($formFieldPart, ']');
                if (!is_array($currentPosition)) {
                    throw new \TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForHashGenerationException('The form field "' . $formField . '" is declared as array, but it collides with a previous form field of the same name which declared the field as string. This is an inconsistency you need to fix inside your Fluid form. (String overridden by Array)', 1255072196);
                }
                if ($i === $formFieldPartsCount - 1) {
                    if (isset($currentPosition[$formFieldPart]) && is_array($currentPosition[$formFieldPart])) {
                        throw new \TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForHashGenerationException('The form field "' . $formField . '" is declared as string, but it collides with a previous form field of the same name which declared the field as array. This is an inconsistency you need to fix inside your Fluid form. (Array overridden by String)', 1255072587);
                    }
                    // Last iteration - add a string
                    if ($formFieldPart === '') {
                        $currentPosition[] = 1;
                    } else {
                        $currentPosition[$formFieldPart] = 1;
                    }
                } else {
                    if ($formFieldPart === '') {
                        throw new \TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForHashGenerationException('The form field "' . $formField . '" is invalid. Reason: "[]" used not as last argument, but somewhere in the middle (like foo[][bar]).', 1255072832);
                    }
                    if (!isset($currentPosition[$formFieldPart])) {
                        $currentPosition[$formFieldPart] = [];
                    }
                    $currentPosition = &$currentPosition[$formFieldPart];
                }
            }
        }
        if ($fieldNamePrefix !== '') {
            $formFieldArray = ($formFieldArray[$fieldNamePrefix] ?? []);
        }
        return $this->serializeAndHashFormFieldArray($formFieldArray);
    }

    /**
     * Serialize and hash the form field array
     *
     * @param array $formFieldArray form field array to be serialized and hashed
     *
     * @return string Hash
     */
    protected function serializeAndHashFormFieldArray(array $formFieldArray)
    {
        $serializedFormFieldArray = serialize($formFieldArray);
        return $this->hashService->appendHmac($serializedFormFieldArray);
    }

    /**
     * Initialize the property mapping configuration in $controllerArguments if
     * the trusted properties are set inside the request.
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Request $request
     * @param \TYPO3\CMS\Extbase\Mvc\Controller\Arguments $controllerArguments
     * @throws BadRequestException
     */
    public function initializePropertyMappingConfigurationFromRequest(\TYPO3\CMS\Extbase\Mvc\Request $request, \TYPO3\CMS\Extbase\Mvc\Controller\Arguments $controllerArguments)
    {
        $trustedPropertiesToken = $request->getInternalArgument('__trustedProperties');
        if (!is_string($trustedPropertiesToken)) {
            return;
        }

        try {
            $serializedTrustedProperties = $this->hashService->validateAndStripHmac($trustedPropertiesToken);
        } catch (InvalidHashException | InvalidArgumentForHashGenerationException $e) {
            throw new BadRequestException('The HMAC of the form could not be validated.', 1581862822);
        }
        $trustedProperties = unserialize($serializedTrustedProperties, ['allowed_classes' => false]);
        foreach ($trustedProperties as $propertyName => $propertyConfiguration) {
            if (!$controllerArguments->hasArgument($propertyName)) {
                continue;
            }
            $propertyMappingConfiguration = $controllerArguments->getArgument($propertyName)->getPropertyMappingConfiguration();
            $this->modifyPropertyMappingConfiguration($propertyConfiguration, $propertyMappingConfiguration);
        }
    }

    /**
     * Modify the passed $propertyMappingConfiguration according to the $propertyConfiguration which
     * has been generated by Fluid. In detail, if the $propertyConfiguration contains
     * an __identity field, we allow modification of objects; else we allow creation.
     *
     * All other properties are specified as allowed properties.
     *
     * @param array $propertyConfiguration
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration $propertyMappingConfiguration
     */
    protected function modifyPropertyMappingConfiguration($propertyConfiguration, \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration $propertyMappingConfiguration)
    {
        if (!is_array($propertyConfiguration)) {
            return;
        }

        if (isset($propertyConfiguration['__identity'])) {
            $propertyMappingConfiguration->setTypeConverterOption(\TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::class, \TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, true);
            unset($propertyConfiguration['__identity']);
        } else {
            $propertyMappingConfiguration->setTypeConverterOption(\TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::class, \TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, true);
        }

        foreach ($propertyConfiguration as $innerKey => $innerValue) {
            if (is_array($innerValue)) {
                $this->modifyPropertyMappingConfiguration($innerValue, $propertyMappingConfiguration->forProperty($innerKey));
            }
            $propertyMappingConfiguration->allowProperties($innerKey);
        }
    }
}
