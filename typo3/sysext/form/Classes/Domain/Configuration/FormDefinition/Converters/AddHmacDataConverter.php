<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Converters;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Configuration\ArrayProcessing\ArrayProcessing;
use TYPO3\CMS\Form\Domain\Configuration\ArrayProcessing\ArrayProcessor;

/**
 * @internal
 */
class AddHmacDataConverter extends AbstractConverter
{

    /**
     * Add a new value "_orig_<propertyName>" as a sibling of the property key.
     * "_orig_<propertyName>" is an array which contains the property value
     * and a hmac hash for the property value.
     * "_orig_<propertyName>" will be used to validate the form definition on saving.
     * @see \TYPO3\CMS\Form\Domain\Configuration\FormDefinitionValidationService::validateFormDefinitionProperties()
     *
     * @param string $key
     * @param mixed $value
     */
    public function __invoke(string $key, $value): void
    {
        $formDefinition = $this->converterDto->getFormDefinition();

        $renderablePathParts = explode('.', $key);
        array_pop($renderablePathParts);

        if (count($renderablePathParts) > 1) {
            $renderablePath = implode('.', $renderablePathParts);
            $currentFormElement = ArrayUtility::getValueByPath($formDefinition, $renderablePath, '.');
        } else {
            $currentFormElement = $formDefinition;
        }

        $propertyCollectionElements = $currentFormElement['finishers'] ?? $currentFormElement['validators'] ?? [];
        $propertyCollectionName = $currentFormElement['type'] === 'Form' ? 'finishers' : 'validators';
        unset($currentFormElement['renderables'], $currentFormElement['finishers'], $currentFormElement['validators']);

        $this->converterDto
            ->setRenderablePathParts($renderablePathParts)
            ->setFormElementIdentifier($value);

        GeneralUtility::makeInstance(ArrayProcessor::class, $currentFormElement)->forEach(
            GeneralUtility::makeInstance(
                ArrayProcessing::class,
                'addHmacData',
                '^(?!(.*\._label|.*\._value)$).*',
                GeneralUtility::makeInstance(
                    AddHmacDataToFormElementPropertyConverter::class,
                    $this->converterDto,
                    $this->sessionToken
                )
            )
        );

        $this->converterDto->setPropertyCollectionName($propertyCollectionName);
        foreach ($propertyCollectionElements as $propertyCollectionIndex => $propertyCollectionElement) {
            $this->converterDto
                ->setPropertyCollectionIndex((int)$propertyCollectionIndex)
                ->setPropertyCollectionElementIdentifier($propertyCollectionElement['identifier']);

            GeneralUtility::makeInstance(ArrayProcessor::class, $propertyCollectionElement)->forEach(
                GeneralUtility::makeInstance(
                    ArrayProcessing::class,
                    'addHmacData',
                    '^(?!(.*\._label|.*\._value)$).*',
                    GeneralUtility::makeInstance(
                        AddHmacDataToPropertyCollectionElementConverter::class,
                        $this->converterDto,
                        $this->sessionToken
                    )
                )
            );
        }
    }
}
