<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Hooks;

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

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Domain\Configuration\ArrayProcessing\ArrayProcessing;
use TYPO3\CMS\Form\Domain\Configuration\ArrayProcessing\ArrayProcessor;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\Configuration\FlexformConfiguration\Processors\FinisherOptionGenerator;
use TYPO3\CMS\Form\Domain\Configuration\FlexformConfiguration\Processors\ProcessorDto;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\NoSuchFileException;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\ParseErrorException;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;
use TYPO3\CMS\Form\Service\TranslationService;

/**
 * Hooks into flex form handling of backend for tt_content form elements:
 *
 * * Adds existing forms to flex form drop down list
 * * Adds finisher settings if "override finishers" is active
 *
 * Scope: backend
 * @internal
 */
class DataStructureIdentifierHook
{

    /**
     * Localisation prefix
     */
    const L10N_PREFIX = 'LLL:EXT:form/Resources/Private/Language/Database.xlf:';

    /**
     * The data structure depends on a current form selection (persistenceIdentifier)
     * and if the field "overrideFinishers" is active. Add both to the identifier to
     * hand these information over to parseDataStructureByIdentifierPostProcess() hook.
     *
     * @param array $fieldTca Incoming field TCA
     * @param string $tableName Handled table
     * @param string $fieldName Handled field
     * @param array $row Current data row
     * @param array $identifier Already calculated identifier
     * @return array Modified identifier
     */
    public function getDataStructureIdentifierPostProcess(
        array $fieldTca,
        string $tableName,
        string $fieldName,
        array $row,
        array $identifier
    ): array {
        if ($tableName === 'tt_content' && $fieldName === 'pi_flexform' && $row['CType'] === 'form_formframework') {
            $currentFlexData = [];
            if (!empty($row['pi_flexform']) && !\is_array($row['pi_flexform'])) {
                $currentFlexData = GeneralUtility::xml2array($row['pi_flexform']);
            }

            // Add selected form value
            $identifier['ext-form-persistenceIdentifier'] = '';
            if (!empty($currentFlexData['data']['sDEF']['lDEF']['settings.persistenceIdentifier']['vDEF'])) {
                $identifier['ext-form-persistenceIdentifier'] = $currentFlexData['data']['sDEF']['lDEF']['settings.persistenceIdentifier']['vDEF'];
            }

            // Add bool - finisher override active or not
            $identifier['ext-form-overrideFinishers'] = false;
            if (
                isset($currentFlexData['data']['sDEF']['lDEF']['settings.overrideFinishers']['vDEF'])
                && (int)$currentFlexData['data']['sDEF']['lDEF']['settings.overrideFinishers']['vDEF'] === 1
            ) {
                $identifier['ext-form-overrideFinishers'] = true;
            }
        }
        return $identifier;
    }

    /**
     * Returns a modified flexform data array.
     *
     * This adds the list of existing form definitions to the form selection drop down
     * and adds sheets to override finisher settings if requested.
     *
     * @param array $dataStructure
     * @param array $identifier
     * @return array
     */
    public function parseDataStructureByIdentifierPostProcess(array $dataStructure, array $identifier): array
    {
        if (isset($identifier['ext-form-persistenceIdentifier'])) {
            try {
                // Add list of existing forms to drop down if we find our key in the identifier
                $formPersistenceManager = GeneralUtility::makeInstance(ObjectManager::class)->get(FormPersistenceManagerInterface::class);
                $formIsAccessible = false;
                foreach ($formPersistenceManager->listForms() as $form) {
                    $invalidFormDefinition = $form['invalid'] ?? false;
                    $hasDeprecatedFileExtension = $form['deprecatedFileExtension'] ?? false;

                    if ($form['location'] === 'storage' && $hasDeprecatedFileExtension) {
                        continue;
                    }

                    if ($form['persistenceIdentifier'] === $identifier['ext-form-persistenceIdentifier']) {
                        $formIsAccessible = true;
                    }

                    if ($invalidFormDefinition || $hasDeprecatedFileExtension) {
                        $dataStructure['sheets']['sDEF']['ROOT']['el']['settings.persistenceIdentifier']['TCEforms']['config']['items'][] = [
                            $form['name'] . ' (' . $form['persistenceIdentifier'] . ')',
                            $form['persistenceIdentifier'],
                            'overlay-missing'
                        ];
                    } else {
                        $dataStructure['sheets']['sDEF']['ROOT']['el']['settings.persistenceIdentifier']['TCEforms']['config']['items'][] = [
                            $form['name'] . ' (' . $form['persistenceIdentifier'] . ')',
                            $form['persistenceIdentifier'],
                            'content-form'
                        ];
                    }
                }

                if (!empty($identifier['ext-form-persistenceIdentifier']) && !$formIsAccessible) {
                    $dataStructure['sheets']['sDEF']['ROOT']['el']['settings.persistenceIdentifier']['TCEforms']['config']['items'][] = [
                        sprintf(
                            $this->getLanguageService()->sL(self::L10N_PREFIX . 'tt_content.preview.inaccessiblePersistenceIdentifier'),
                            $identifier['ext-form-persistenceIdentifier']
                        ),
                        $identifier['ext-form-persistenceIdentifier'],
                    ];
                }

                // If a specific form is selected and if finisher override is active, add finisher sheets
                if (!empty($identifier['ext-form-persistenceIdentifier'])
                    && $formIsAccessible
                    && isset($identifier['ext-form-overrideFinishers'])
                    && $identifier['ext-form-overrideFinishers'] === true
                ) {
                    $persistenceIdentifier = $identifier['ext-form-persistenceIdentifier'];
                    $formDefinition = $formPersistenceManager->load($persistenceIdentifier);
                    $newSheets = $this->getAdditionalFinisherSheets($persistenceIdentifier, $formDefinition);
                    ArrayUtility::mergeRecursiveWithOverrule(
                        $dataStructure,
                        $newSheets
                    );
                }
            } catch (NoSuchFileException $e) {
                $dataStructure = $this->addSelectedPersistenceIdentifier($identifier['ext-form-persistenceIdentifier'], $dataStructure);
                $this->addInvalidFrameworkConfigurationFlashMessage($e);
            } catch (ParseErrorException $e) {
                $dataStructure = $this->addSelectedPersistenceIdentifier($identifier['ext-form-persistenceIdentifier'], $dataStructure);
                $this->addInvalidFrameworkConfigurationFlashMessage($e);
            }
        }
        return $dataStructure;
    }

    /**
     * Returns additional flexform sheets with finisher fields
     *
     * @param string $persistenceIdentifier Current persistence identifier
     * @param array $formDefinition The form definition
     * @return array
     */
    protected function getAdditionalFinisherSheets(string $persistenceIdentifier, array $formDefinition): array
    {
        if (!isset($formDefinition['finishers']) || empty($formDefinition['finishers'])) {
            return [];
        }

        $prototypeName = $formDefinition['prototypeName'] ?? 'standard';
        $prototypeConfiguration = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(ConfigurationService::class)
            ->getPrototypeConfiguration($prototypeName);

        if (!isset($prototypeConfiguration['finishersDefinition']) || empty($prototypeConfiguration['finishersDefinition'])) {
            return [];
        }

        $formIdentifier = $formDefinition['identifier'];
        $finishersDefinition = $prototypeConfiguration['finishersDefinition'];

        $sheets = ['sheets' => []];
        foreach ($formDefinition['finishers'] as $formFinisherDefinition) {
            $finisherIdentifier = $formFinisherDefinition['identifier'];
            if (!isset($finishersDefinition[$finisherIdentifier]['FormEngine']['elements'])) {
                continue;
            }
            $sheetIdentifier = $this->buildFlexformSheetIdentifier(
                $persistenceIdentifier,
                $prototypeName,
                $formIdentifier,
                $finisherIdentifier
            );

            $finishersDefinition = $this->translateFinisherDefinitionByIdentifier(
                $finisherIdentifier,
                $finishersDefinition,
                $prototypeConfiguration
            );

            $prototypeFinisherDefinition = $finishersDefinition[$finisherIdentifier];
            $finisherLabel = $prototypeFinisherDefinition['FormEngine']['label'] ?? '';
            $sheet = $this->initializeNewSheetArray($sheetIdentifier, $finisherLabel);

            $converterDto = GeneralUtility::makeInstance(
                ProcessorDto::class,
                $finisherIdentifier,
                $prototypeFinisherDefinition,
                $formFinisherDefinition
            );

            // Iterate over all `TYPO3.CMS.Form.prototypes.<prototypeName>.finishersDefinition.<finisherIdentifier>.FormEngine.elements` values
            // and convert them to FlexForm elements
            GeneralUtility::makeInstance(ArrayProcessor::class, $prototypeFinisherDefinition['FormEngine']['elements'])->forEach(
                GeneralUtility::makeInstance(
                    ArrayProcessing::class,
                    'convertToFlexFormSheets',
                    '^(.*)\.config\.type$',
                    GeneralUtility::makeInstance(FinisherOptionGenerator::class, $converterDto)
                )
            );

            $sheet[$sheetIdentifier]['ROOT']['el'] = $converterDto->getResult();
            ArrayUtility::mergeRecursiveWithOverrule($sheets['sheets'], $sheet);
        }
        if (empty($sheets['sheets'])) {
            return [];
        }

        return $sheets;
    }

    /**
     * Boilerplate XML array of a new sheet
     *
     * @param string $sheetIdentifier
     * @param string $finisherName
     * @throws \InvalidArgumentException
     * @return array
     */
    protected function initializeNewSheetArray(string $sheetIdentifier, string $finisherName): array
    {
        if (empty($sheetIdentifier)) {
            throw new \InvalidArgumentException('$sheetIdentifier must not be empty.', 1472060918);
        }
        if (empty($finisherName)) {
            throw new \InvalidArgumentException('$finisherName must not be empty.', 1472060919);
        }

        return [
            $sheetIdentifier => [
                'ROOT' => [
                    'TCEforms' => [
                        'sheetTitle' => $finisherName,
                    ],
                    'type' => 'array',
                    'el' => [],
                ],
            ],
        ];
    }

    /**
     * @param string $persistenceIdentifier
     * @param array $dataStructure
     * @return array
     */
    protected function addSelectedPersistenceIdentifier(string $persistenceIdentifier, array $dataStructure): array
    {
        if (!empty($persistenceIdentifier)) {
            $dataStructure['sheets']['sDEF']['ROOT']['el']['settings.persistenceIdentifier']['TCEforms']['config']['items'][] = [
                sprintf(
                    $this->getLanguageService()->sL(self::L10N_PREFIX . 'tt_content.preview.inaccessiblePersistenceIdentifier'),
                    $persistenceIdentifier
                ),
                $persistenceIdentifier,
            ];
        }

        return $dataStructure;
    }

    /**
     * @param \Exception $e
     */
    protected function addInvalidFrameworkConfigurationFlashMessage(\Exception $e)
    {
        $messageText = sprintf(
            $this->getLanguageService()->sL(self::L10N_PREFIX . 'tt_content.preview.invalidFrameworkConfiguration.text'),
            $e->getMessage()
        );

        GeneralUtility::makeInstance(ObjectManager::class)
            ->get(FlashMessageService::class)
            ->getMessageQueueByIdentifier('core.template.flashMessages')
            ->enqueue(
                GeneralUtility::makeInstance(
                    FlashMessage::class,
                    $messageText,
                    $this->getLanguageService()->sL(self::L10N_PREFIX . 'tt_content.preview.invalidFrameworkConfiguration.title'),
                    AbstractMessage::ERROR,
                    true
                )
            );
    }

    /**
     * @param string $persistenceIdentifier
     * @param string $prototypeName
     * @param string $formIdentifier
     * @param string $finisherIdentifier
     * @return string
     */
    protected function buildFlexformSheetIdentifier(
        string $persistenceIdentifier,
        string $prototypeName,
        string $formIdentifier,
        string $finisherIdentifier
    ): string {
        return md5(
            implode('', [
                $persistenceIdentifier,
                $prototypeName,
                $formIdentifier,
                $finisherIdentifier
            ])
        );
    }

    /**
     * @param string $finisherIdentifier
     * @param array $finishersDefinition
     * @param array $prototypeConfiguration
     * @return array
     */
    protected function translateFinisherDefinitionByIdentifier(
        string $finisherIdentifier,
        array $finishersDefinition,
        array $prototypeConfiguration
    ): array {
        if (isset($finishersDefinition[$finisherIdentifier]['FormEngine']['translationFile'])) {
            $translationFile = $finishersDefinition[$finisherIdentifier]['FormEngine']['translationFile'];
        } else {
            $translationFile = $prototypeConfiguration['formEngine']['translationFile'];
        }

        $finishersDefinition[$finisherIdentifier]['FormEngine'] = TranslationService::getInstance()->translateValuesRecursive(
            $finishersDefinition[$finisherIdentifier]['FormEngine'],
            $translationFile
        );

        return $finishersDefinition;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
