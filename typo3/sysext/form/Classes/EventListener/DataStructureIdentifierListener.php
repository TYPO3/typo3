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

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureIdentifierInitializedEvent;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface as ExtbaseConfigurationManagerInterface;
use TYPO3\CMS\Form\Domain\Configuration\ArrayProcessing\ArrayProcessing;
use TYPO3\CMS\Form\Domain\Configuration\ArrayProcessing\ArrayProcessor;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\Configuration\FlexformConfiguration\Processors\FinisherOptionGenerator;
use TYPO3\CMS\Form\Domain\Configuration\FlexformConfiguration\Processors\ProcessorDto;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface as ExtFormConfigurationManagerInterface;
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
#[Autoconfigure(public: true)]
readonly class DataStructureIdentifierListener
{
    /**
     * Some dependencies are declared lazy since they otherwise collide with
     * early instance creations of FE PageRepository when called in sitations
     * where DB does not yet exist (especially acceptance test setup)
     * @todo: Clean up __construct() / init() of PageRepository!
     */
    public function __construct(
        protected FormPersistenceManagerInterface $formPersistenceManager,
        #[Autowire(lazy: true)]
        protected ConfigurationService $configurationService,
        #[Autowire(lazy: true)]
        protected TranslationService $translationService,
        protected FlashMessageService $flashMessageService,
        #[Autowire(lazy: true)]
        protected ExtbaseConfigurationManagerInterface $extbaseConfigurationManager,
        #[Autowire(lazy: true)]
        protected ExtFormConfigurationManagerInterface $extFormConfigurationManager,
    ) {}

    /**
     * The data structure depends on a current form selection (persistenceIdentifier)
     * and if the field "overrideFinishers" is active. Add both to the identifier to
     * hand these information over to parseDataStructureByIdentifierPostProcess() hook.
     */
    #[AsEventListener('form-framework/modify-data-structure-identifier')]
    public function modifyDataStructureIdentifier(AfterFlexFormDataStructureIdentifierInitializedEvent $event): void
    {
        $row = $event->getRow();
        if (($row['CType'] ?? '') !== 'form_formframework' || $event->getTableName() !== 'tt_content' || $event->getFieldName() !== 'pi_flexform') {
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
        if (isset($currentFlexData['data']['sDEF']['lDEF']['settings.overrideFinishers']['vDEF'])
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
    #[AsEventListener('form-framework/modify-data-structure')]
    public function modifyDataStructure(AfterFlexFormDataStructureParsedEvent $event): void
    {
        $identifier = $event->getIdentifier();
        if (!isset($identifier['ext-form-persistenceIdentifier'])) {
            return;
        }
        $dataStructure = $event->getDataStructure();
        // We need $this->extbaseConfigurationManager to work at this point. This is directly needed as
        // input for FormPersistenceManager, and indirectly for ConfigurationService.
        // The ConfigurationManager of ext:form needs ext:extbase ConfigurationManager to retrieve basic TS
        // settings (for "module.tx_form" allowed form storages). ConfigurationManager of extbase should *usually*
        // only be called in extbase context and needs a Request, which is usually set by extbase bootstrap.
        // We are however not in extbase context here.
        // The solution is ugly, but at least makes the situation explicit:
        // We fetch the request from $GLOBALS['TYPO3_REQUEST'] and actively fake a request in case this is not set.
        // The latter may happen in CLI context, if FlexFormTools->parseDataStructureByIdentifier() is used by CLI (really?).
        // @todo: There are various options to deal with this. First, the BE extbase ConfigurationManager could
        //        make the dependency to request optional. The fact that extbase BE functionality relies on FE TS
        //        is the main and long standing issue here. If that is possible, this event may not need to
        //        set the request anymore, and it would probably be enough for ext:form to rely on "global" and
        //        not page-id dependent TS.
        //        Secondly, the FlexFormTools data structure identifier stuff could be made request aware and
        //        could hand over a request to the event. DS identifier retrieval however is low-level and we
        //        may not want to have this dependency at all in this layer.
        //        Another option might be to make *this* part of ext:form extbase free and have an own TS
        //        layer to fetch TS that does not rely on current request. But that is something we may
        //        not want, either, since it may break too much?
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface) {
            $request = $GLOBALS['TYPO3_REQUEST'];
        } else {
            $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        }
        // @todo: extFormConfigurationManager->setRequest($request) needs to fall with higher priority than above todo!
        $this->extFormConfigurationManager->setRequest($request);
        $this->extbaseConfigurationManager->setRequest($request);
        $formSettings = $this->extFormConfigurationManager->getConfiguration(ExtFormConfigurationManagerInterface::CONFIGURATION_TYPE_YAML_SETTINGS, 'form');
        try {
            // Add list of existing forms to drop down if we find our key in the identifier
            $formIsAccessible = false;
            foreach ($this->formPersistenceManager->listForms($formSettings) as $form) {
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
                $languageService = $this->getLanguageService();
                $dataStructure['sheets']['sDEF']['ROOT']['el']['settings.persistenceIdentifier']['config']['items'][] = [
                    'label' => sprintf(
                        $languageService->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:tt_content.preview.inaccessiblePersistenceIdentifier'),
                        $identifier['ext-form-persistenceIdentifier']
                    ),
                    'value' => $identifier['ext-form-persistenceIdentifier'],
                ];
            }
            // If a specific form is selected and if finisher override is active, add finisher sheets
            if (!empty($identifier['ext-form-persistenceIdentifier']) && $formIsAccessible) {
                $persistenceIdentifier = $identifier['ext-form-persistenceIdentifier'];
                $formDefinition = $this->formPersistenceManager->load($persistenceIdentifier, $formSettings, []);
                $translationFile = 'LLL:EXT:form/Resources/Private/Language/Database.xlf';
                $dataStructure['sheets']['sDEF']['ROOT']['el']['settings.overrideFinishers'] = [
                    'label' => $translationFile . ':tt_content.pi_flexform.formframework.overrideFinishers',
                    'onChange' => 'reload',
                    'config' => [
                        'type' => 'check',
                    ],
                ];
                $newSheets = [];
                if (!empty($formDefinition['finishers'])) {
                    $prototypeName = $formDefinition['prototypeName'] ?? 'standard';
                    $prototypeConfiguration = $this->configurationService->getPrototypeConfiguration($prototypeName);
                    $newSheets = $this->getAdditionalFinisherSheets($persistenceIdentifier, $formDefinition, $prototypeName, $prototypeConfiguration);
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
                    ArrayUtility::mergeRecursiveWithOverrule($dataStructure, $newSheets);
                }
            }
        } catch (NoSuchFileException|ParseErrorException $e) {
            $dataStructure = $this->addSelectedPersistenceIdentifier($identifier['ext-form-persistenceIdentifier'], $dataStructure);
            $this->addInvalidFrameworkConfigurationFlashMessage($e, $identifier['ext-form-persistenceIdentifier']);
        }
        $event->setDataStructure($dataStructure);
    }

    /**
     * Returns additional flexform sheets with finisher fields
     *
     * @param string $persistenceIdentifier Current persistence identifier
     * @param array $formDefinition The form definition
     */
    protected function getAdditionalFinisherSheets(string $persistenceIdentifier, array $formDefinition, string $prototypeName, array $prototypeConfiguration): array
    {
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
            $sheetIdentifier = $this->buildFlexformSheetIdentifier($persistenceIdentifier, $prototypeName, $formIdentifier, $finisherIdentifier);
            $finishersDefinition = $this->translateFinisherDefinitionByIdentifier($finisherIdentifier, $finishersDefinition, $prototypeConfiguration);
            $prototypeFinisherDefinition = $finishersDefinition[$finisherIdentifier];
            $finisherLabel = $prototypeFinisherDefinition['FormEngine']['label'] ?? '';
            $sheet = $this->initializeNewSheetArray($sheetIdentifier, $finisherLabel);
            $converterDto = GeneralUtility::makeInstance(ProcessorDto::class, $finisherIdentifier, $prototypeFinisherDefinition, $formFinisherDefinition);
            // Remove all container elements "el" from sections beforehand.
            // These should not be matched by the regex below. This greatly reduces headaches.
            $elements = $prototypeFinisherDefinition['FormEngine']['elements'];
            foreach ($elements as $key => $element) {
                if ($element['section'] ?? false) {
                    unset($elements[$key]['el']);
                }
            }
            // Iterate over all `prototypes.<prototypeName>.finishersDefinition.<finisherIdentifier>.FormEngine.elements`
            // values and convert them to FlexForm elements.
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
     * Boilerplate XML array of a new sheet.
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
            $languageService = $this->getLanguageService();
            $dataStructure['sheets']['sDEF']['ROOT']['el']['settings.persistenceIdentifier']['config']['items'][] = [
                'label' => sprintf(
                    $languageService->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:tt_content.preview.inaccessiblePersistenceIdentifier'),
                    $persistenceIdentifier
                ),
                'value' => $persistenceIdentifier,
            ];
        }
        return $dataStructure;
    }

    protected function addInvalidFrameworkConfigurationFlashMessage(\Exception $e, string $identifier = ''): void
    {
        $languageService = $this->getLanguageService();
        $this->flashMessageService
            ->getMessageQueueByIdentifier('core.template.flashMessages')
            ->enqueue(
                GeneralUtility::makeInstance(
                    FlashMessage::class,
                    sprintf(
                        $languageService->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:tt_content.preview.invalidFrameworkConfiguration.text'),
                        $identifier,
                        $e->getMessage()
                    ),
                    $languageService->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:tt_content.preview.invalidFrameworkConfiguration.title'),
                    ContextualFeedbackSeverity::ERROR,
                    true
                )
            );
    }

    protected function buildFlexformSheetIdentifier(string $persistenceIdentifier, string $prototypeName, string $formIdentifier, string $finisherIdentifier): string
    {
        return md5($persistenceIdentifier . $prototypeName . $formIdentifier . $finisherIdentifier);
    }

    protected function translateFinisherDefinitionByIdentifier(string $finisherIdentifier, array $finishersDefinition, array $prototypeConfiguration): array
    {
        $translationFiles = $finishersDefinition[$finisherIdentifier]['FormEngine']['translationFiles'] ?? $prototypeConfiguration['formEngine']['translationFiles'];
        $finishersDefinition[$finisherIdentifier]['FormEngine'] = $this->translationService->translateValuesRecursive(
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
