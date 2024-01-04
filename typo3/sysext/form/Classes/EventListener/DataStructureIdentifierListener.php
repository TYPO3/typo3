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

namespace TYPO3\CMS\Form\EventListener;

use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureIdentifierInitializedEvent;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
 * Event listener extending the flex form handling for tt_content form elements (CType: form_formframework):
 *
 * * Adds existing forms to flex form drop down list
 * * Adds finisher settings if Option "Override finisher settings" is active
 *
 * Scope: backend
 * @internal
 */
class DataStructureIdentifierListener
{
    /**
     * The data structure depends on a current form selection (persistenceIdentifier)
     * and if the field "overrideFinishers" is active. Add both to the identifier to
     * hand these information over to parseDataStructureByIdentifierPostProcess() hook.
     */
    public function modifyDataStructureIdentifier(AfterFlexFormDataStructureIdentifierInitializedEvent $event): void
    {
        $row = $event->getRow();
        if (($row['CType'] ?? '') !== 'form_formframework'
            || $event->getTableName() !== 'tt_content'
            || $event->getFieldName() !== 'pi_flexform'
        ) {
            return;
        }

        $identifier = $event->getIdentifier();

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
        $identifier['ext-form-overrideFinishers'] = '';
        if (
            isset($currentFlexData['data']['sDEF']['lDEF']['settings.overrideFinishers']['vDEF'])
            && (int)$currentFlexData['data']['sDEF']['lDEF']['settings.overrideFinishers']['vDEF'] === 1
        ) {
            $identifier['ext-form-overrideFinishers'] = 'enabled';
        }

        $event->setIdentifier($identifier);
    }

    /**
     * Adds the list of existing form definitions to the form selection drop down
     * and adds sheets to override finisher settings if requested.
     */
    public function modifyDataStructure(AfterFlexFormDataStructureParsedEvent $event): void
    {
        $identifier = $event->getIdentifier();
        if (!isset($identifier['ext-form-persistenceIdentifier'])) {
            return;
        }

        $dataStructure = $event->getDataStructure();
        try {
            // Add list of existing forms to drop down if we find our key in the identifier
            $formPersistenceManager = GeneralUtility::makeInstance(FormPersistenceManagerInterface::class);
            $formIsAccessible = false;
            foreach ($formPersistenceManager->listForms() as $form) {
                $invalidFormDefinition = $form['invalid'] ?? false;

                if ($form['persistenceIdentifier'] === $identifier['ext-form-persistenceIdentifier']) {
                    $formIsAccessible = true;
                }

                if ($invalidFormDefinition) {
                    $dataStructure['sheets']['sDEF']['ROOT']['el']['settings.persistenceIdentifier']['config']['items'][] = [
                        'label' => $form['name'] . ' (' . $form['persistenceIdentifier'] . ')',
                        'value' => $form['persistenceIdentifier'],
                        'icon' => 'overlay-missing',
                    ];
                } else {
                    $dataStructure['sheets']['sDEF']['ROOT']['el']['settings.persistenceIdentifier']['config']['items'][] = [
                        'label' => $form['name'] . ' (' . $form['persistenceIdentifier'] . ')',
                        'value' => $form['persistenceIdentifier'],
                        'icon' => 'content-form',
                    ];
                }
            }

            if (!empty($identifier['ext-form-persistenceIdentifier']) && !$formIsAccessible) {
                $dataStructure['sheets']['sDEF']['ROOT']['el']['settings.persistenceIdentifier']['config']['items'][] = [
                    'label' => sprintf(
                        $this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:tt_content.preview.inaccessiblePersistenceIdentifier'),
                        $identifier['ext-form-persistenceIdentifier']
                    ),
                    'value' => $identifier['ext-form-persistenceIdentifier'],
                ];
            }

            // If a specific form is selected and if finisher override is active, add finisher sheets
            if (!empty($identifier['ext-form-persistenceIdentifier']) && $formIsAccessible) {
                $persistenceIdentifier = $identifier['ext-form-persistenceIdentifier'];
                $formDefinition = $formPersistenceManager->load($persistenceIdentifier);

                $translationFile = 'LLL:EXT:form/Resources/Private/Language/Database.xlf';
                $dataStructure['sheets']['sDEF']['ROOT']['el']['settings.overrideFinishers'] = [
                    'label' => $translationFile . ':tt_content.pi_flexform.formframework.overrideFinishers',
                    'onChange' => 'reload',
                    'config' => [
                        'type' => 'check',
                    ],
                ];

                $newSheets = [];

                if (isset($formDefinition['finishers']) && !empty($formDefinition['finishers'])) {
                    $newSheets = $this->getAdditionalFinisherSheets($persistenceIdentifier, $formDefinition);
                }

                if (empty($newSheets)) {
                    ArrayUtility::mergeRecursiveWithOverrule(
                        $dataStructure['sheets']['sDEF']['ROOT']['el']['settings.overrideFinishers'],
                        [
                            'description' => $translationFile . ':tt_content.pi_flexform.formframework.overrideFinishers.empty',
                            'config' => [
                                'readOnly' => true,
                            ],
                        ]
                    );
                }

                if ($identifier['ext-form-overrideFinishers'] === 'enabled') {
                    ArrayUtility::mergeRecursiveWithOverrule(
                        $dataStructure,
                        $newSheets
                    );
                }
            }
        } catch (NoSuchFileException|ParseErrorException $e) {
            $dataStructure = $this->addSelectedPersistenceIdentifier($identifier['ext-form-persistenceIdentifier'], $dataStructure);
            $this->addInvalidFrameworkConfigurationFlashMessage($e);
        }

        $event->setDataStructure($dataStructure);
    }

    /**
     * Returns additional flexform sheets with finisher fields
     *
     * @param string $persistenceIdentifier Current persistence identifier
     * @param array $formDefinition The form definition
     */
    protected function getAdditionalFinisherSheets(string $persistenceIdentifier, array $formDefinition): array
    {
        if (empty($formDefinition['finishers'])) {
            return [];
        }

        $prototypeName = $formDefinition['prototypeName'] ?? 'standard';
        $prototypeConfiguration = GeneralUtility::makeInstance(ConfigurationService::class)
            ->getPrototypeConfiguration($prototypeName);

        if (empty($prototypeConfiguration['finishersDefinition'])) {
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

            // Remove all container elements "el" from sections beforehand.
            // These should not be matched by the regex below.
            // This greatly reduces headaches.
            $elements = $prototypeFinisherDefinition['FormEngine']['elements'];
            foreach ($elements as $key => $element) {
                if ($element['section'] ?? false) {
                    unset($elements[$key]['el']);
                }
            }

            // Iterate over all `prototypes.<prototypeName>.finishersDefinition.<finisherIdentifier>.FormEngine.elements` values
            // and convert them to FlexForm elements
            GeneralUtility::makeInstance(ArrayProcessor::class, $elements)->forEach(
                GeneralUtility::makeInstance(
                    ArrayProcessing::class,
                    'convertToFlexFormSheets',
                    // Parse top level elements and section containers.
                    '^(.*)(?:\.config\.type|\.section)$',
                    GeneralUtility::makeInstance(FinisherOptionGenerator::class, $converterDto)
                )
            );

            $sheet[$sheetIdentifier]['ROOT']['el'] = $converterDto->getResult();
            ArrayUtility::mergeRecursiveWithOverrule($sheets['sheets'], $sheet);
        }

        return $sheets;
    }

    /**
     * Boilerplate XML array of a new sheet
     *
     * @throws \InvalidArgumentException
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
                    'sheetTitle' => $finisherName,
                    'type' => 'array',
                    'el' => [],
                ],
            ],
        ];
    }

    protected function addSelectedPersistenceIdentifier(string $persistenceIdentifier, array $dataStructure): array
    {
        if (!empty($persistenceIdentifier)) {
            $dataStructure['sheets']['sDEF']['ROOT']['el']['settings.persistenceIdentifier']['config']['items'][] = [
                'label' => sprintf(
                    $this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:tt_content.preview.inaccessiblePersistenceIdentifier'),
                    $persistenceIdentifier
                ),
                'value' => $persistenceIdentifier,
            ];
        }

        return $dataStructure;
    }

    protected function addInvalidFrameworkConfigurationFlashMessage(\Exception $e): void
    {
        $messageText = sprintf(
            $this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:tt_content.preview.invalidFrameworkConfiguration.text'),
            $e->getMessage()
        );

        GeneralUtility::makeInstance(FlashMessageService::class)
            ->getMessageQueueByIdentifier('core.template.flashMessages')
            ->enqueue(
                GeneralUtility::makeInstance(
                    FlashMessage::class,
                    $messageText,
                    $this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:tt_content.preview.invalidFrameworkConfiguration.title'),
                    ContextualFeedbackSeverity::ERROR,
                    true
                )
            );
    }

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
                $finisherIdentifier,
            ])
        );
    }

    protected function translateFinisherDefinitionByIdentifier(
        string $finisherIdentifier,
        array $finishersDefinition,
        array $prototypeConfiguration
    ): array {
        if (isset($finishersDefinition[$finisherIdentifier]['FormEngine']['translationFiles'])) {
            $translationFiles = $finishersDefinition[$finisherIdentifier]['FormEngine']['translationFiles'];
        } else {
            $translationFiles = $prototypeConfiguration['formEngine']['translationFiles'];
        }

        $finishersDefinition[$finisherIdentifier]['FormEngine'] = GeneralUtility::makeInstance(TranslationService::class)->translateValuesRecursive(
            $finishersDefinition[$finisherIdentifier]['FormEngine'],
            $translationFiles
        );

        return $finishersDefinition;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
