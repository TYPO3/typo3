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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Configuration\ArrayProcessing\ArrayProcessing;
use TYPO3\CMS\Form\Domain\Configuration\ArrayProcessing\ArrayProcessor;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Converters\AddHmacDataConverter;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Converters\ConverterDto;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Converters\FinisherTranslationLanguageConverter;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Converters\RemoveHmacDataConverter;

/**
 * @internal
 */
class FormDefinitionConversionService implements SingletonInterface
{

    /**
     * Add a new value "_orig_<propertyName>" for each scalar property value
     * within the form definition as a sibling of the property key.
     * "_orig_<propertyName>" is an array which contains the property value
     * and a hmac hash for the property value.
     * "_orig_<propertyName>" will be used to validate the form definition on saving.
     * @see \TYPO3\CMS\Form\Domain\Configuration\FormDefinitionValidationService::validateFormDefinitionProperties()
     *
     * @param array $formDefinition
     * @return array
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
     *
     * @param array $formDefinition
     * @return array
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
     *
     * @param array $formDefinition
     * @return array
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

    /**
     * @param string $sessionToken
     */
    protected function persistSessionToken(string $sessionToken)
    {
        $this->getBackendUser()->setAndSaveSessionData('extFormProtectionSessionToken', $sessionToken);
    }

    /**
     * Generates the random token which is used in the hash for the form tokens.
     *
     * @return string
     */
    protected function generateSessionToken()
    {
        return GeneralUtility::makeInstance(Random::class)->generateRandomHexString(64);
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
