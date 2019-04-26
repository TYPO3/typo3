<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Controller;

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
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Form\Domain\Configuration\ArrayProcessing\ArrayProcessing;
use TYPO3\CMS\Form\Domain\Configuration\ArrayProcessing\ArrayProcessor;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Converters\FinisherOptionsFlexFormOverridesConverter;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Converters\FlexFormFinisherOverridesConverterDto;
use TYPO3\CMS\Form\Mvc\Configuration\TypoScriptService;

/**
 * The frontend controller
 *
 * Scope: frontend
 * @internal
 */
class FormFrontendController extends ActionController
{

    /**
     * @var \TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface
     */
    protected $formPersistenceManager;

    /**
     * @param \TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface $formPersistenceManager
     * @internal
     */
    public function injectFormPersistenceManager(\TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface $formPersistenceManager)
    {
        $this->formPersistenceManager = $formPersistenceManager;
    }

    /**
     * Take the form which should be rendered from the plugin settings
     * and overlay the formDefinition with additional data from
     * flexform and typoscript settings.
     * This method is used directly to display the first page from the
     * formDefinition because its cached.
     *
     * @internal
     */
    public function renderAction()
    {
        $formDefinition = [];
        if (!empty($this->settings['persistenceIdentifier'])) {
            $formDefinition = $this->formPersistenceManager->load($this->settings['persistenceIdentifier']);
            $formDefinition['persistenceIdentifier'] = $this->settings['persistenceIdentifier'];
            $formDefinition = $this->overrideByTypoScriptSettings($formDefinition);
            $formDefinition = $this->overrideByFlexFormSettings($formDefinition);
            $formDefinition = ArrayUtility::setValueByPath($formDefinition, 'renderingOptions._originalIdentifier', $formDefinition['identifier'], '.');
            $formDefinition['identifier'] .= '-' . $this->configurationManager->getContentObject()->data['uid'];
        }
        $this->view->assign('formConfiguration', $formDefinition);
    }

    /**
     * This method is used to display all pages / finishers except the
     * first page because its non cached.
     *
     * @internal
     */
    public function performAction()
    {
        $this->forward('render');
    }

    /**
     * Override the formDefinition with additional data from the Flexform
     * settings. For now, only finisher settings are overridable.
     *
     * @param array $formDefinition
     * @return array
     */
    protected function overrideByFlexFormSettings(array $formDefinition): array
    {
        if (isset($formDefinition['finishers'])) {
            foreach ($formDefinition['finishers'] as $index => $formFinisherDefinition) {
                $finisherIdentifier = $formFinisherDefinition['identifier'];
                $prototypeName = $formDefinition['prototypeName'] ?? 'standard';

                if ($this->settings['overrideFinishers'] && isset($this->settings['finishers'][$finisherIdentifier])) {
                    $configurationService = $this->objectManager->get(ConfigurationService::class);
                    $prototypeConfiguration = $configurationService->getPrototypeConfiguration($prototypeName);
                    $flexFormSheetSettings = $this->settings;
                    $prototypeFinisherDefinition = $prototypeConfiguration['finishersDefinition'][$finisherIdentifier] ?? [];
                    $converterDto = GeneralUtility::makeInstance(
                        FlexFormFinisherOverridesConverterDto::class,
                        $formFinisherDefinition,
                        $finisherIdentifier,
                        $flexFormSheetSettings
                    );

                    // Iterate over all `TYPO3.CMS.Form.prototypes.<prototypeName>.finishersDefinition.<finisherIdentifier>.FormEngine.elements` values
                    GeneralUtility::makeInstance(ArrayProcessor::class, $prototypeFinisherDefinition['FormEngine']['elements'])->forEach(
                        GeneralUtility::makeInstance(
                            ArrayProcessing::class,
                            'modifyFinisherOptionsFromFlexFormOverrides',
                            '^(.*)\.config\.type$',
                            GeneralUtility::makeInstance(FinisherOptionsFlexFormOverridesConverter::class, $converterDto)
                        )
                    );

                    $formDefinition['finishers'][$index] = $converterDto->getFinisherDefinition();
                }
            }
        }
        return $formDefinition;
    }

    /**
     * Every formDefinition setting are overridable by typoscript.
     * If the typoscript configuration path
     * plugin.tx_form.settings.formDefinitionOverrides.<identifier>
     * exists, this settings are merged into the formDefinition.
     *
     * @param array $formDefinition
     * @return array
     */
    protected function overrideByTypoScriptSettings(array $formDefinition): array
    {
        if (
            isset($this->settings['formDefinitionOverrides'][$formDefinition['identifier']])
            && !empty($this->settings['formDefinitionOverrides'][$formDefinition['identifier']])
        ) {
            ArrayUtility::mergeRecursiveWithOverrule(
                $formDefinition,
                $this->settings['formDefinitionOverrides'][$formDefinition['identifier']]
            );
            $formDefinition = $this->objectManager->get(TypoScriptService::class)
                ->resolvePossibleTypoScriptConfiguration($formDefinition);
        }
        return $formDefinition;
    }
}
