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

namespace TYPO3\CMS\Scheduler\Form\Element;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Form\Element\CheckboxElement;
use TYPO3\CMS\Backend\Form\Element\DatetimeElement;
use TYPO3\CMS\Backend\Form\Element\InputTextElement;
use TYPO3\CMS\Backend\Form\Element\RadioElement;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Domain\DateTimeFactory;
use TYPO3\CMS\Core\Domain\DateTimeFormat;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Scheduler\Execution;

/**
 * Creates an element to show a lot of details.
 *
 * This is rendered for config type=json, renderType=schedulerTimingOptions
 *
 * @internal This is a specific hook implementation and is not considered part of the Public TYPO3 API.
 */
class TimingOptionsElement extends AbstractFormElement
{
    /**
     * Default field information enabled for this element.
     *
     * @var array
     */
    protected $defaultFieldInformation = [
        'tcaDescription' => [
            'renderType' => 'tcaDescription',
        ],
    ];

    public function __construct(
        private readonly ViewFactoryInterface $viewFactory,
        private readonly Context $context,
    ) {}

    public function render(): array
    {
        $languageService = $this->getLanguageService();
        $resultArray = $this->initializeResultArray();
        $parameterArray = $this->data['parameterArray'];
        $itemValue = $parameterArray['itemFormElValue'];
        $itemName = $parameterArray['itemFormElName'];

        if (is_array($itemValue) && $itemValue !== []) {
            $executionDetails = Execution::createFromDetails($itemValue);
        } else {
            $executionDetails = new Execution();
            // Set the default value to "in 5 minutes"
            $executionDetails->setStart($this->context->getPropertyFromAspect('date', 'accessTime') + (5 * 60));
        }

        $fieldsHtml = '';

        $runningType = GeneralUtility::makeInstance(RadioElement::class);
        $runningType->data = $this->data;
        $runningType->data['containerFieldName'] = 'runningType';
        $runningType->data['parameterArray']['fieldConf']['label'] = htmlspecialchars($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:runningType'));
        $runningType->data['parameterArray']['itemFormElName'] .= '[runningType]';
        $runningType->data['parameterArray']['fieldConf']['config']['items'] = [
            ['label' => $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.type.single'), 'value' => 1],
            ['label' => $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.type.recurring'), 'value' => 2],
        ];
        $runningType->data['parameterArray']['itemFormElValue'] = $executionDetails->isSingleRun() ? 1 : 2;
        $runningType->data['parameterArray']['fieldChangeFunc'] = [];
        $subFieldResult = $runningType->render();
        $resultArray['javaScriptModules'] = array_merge($resultArray['javaScriptModules'], $subFieldResult['javaScriptModules']);
        $fieldsHtml .= '<div class="form-group col-sm-6 t3js-timing-options-runningType">' . str_replace('"form-check"', '"form-check form-inline me-2"', $subFieldResult['html']) . '</div>';

        $multiple = GeneralUtility::makeInstance(CheckboxElement::class);
        $multiple->data = $this->data;
        $multiple->data['containerFieldName'] = 'multiple';
        $multiple->data['parameterArray']['fieldConf']['label'] = htmlspecialchars($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.parallel.long'));
        $multiple->data['parameterArray']['itemFormElName'] .= '[multiple]';
        $multiple->data['parameterArray']['fieldConf']['config']['items'] = [];
        $multiple->data['parameterArray']['fieldChangeFunc'] = [];
        $multiple->data['parameterArray']['itemFormElValue'] = $executionDetails->isParallelExecutionAllowed();
        $subFieldResult = $multiple->render();
        $resultArray['javaScriptModules'] = array_merge($resultArray['javaScriptModules'], $subFieldResult['javaScriptModules']);
        $fieldsHtml .= '<div class="form-group col-sm-6 t3js-timing-options-parallel">' . $subFieldResult['html'] . '</div>';

        $start = GeneralUtility::makeInstance(DatetimeElement::class);
        $start->data = $this->data;
        $start->data['containerFieldName'] = 'start';
        $start->data['parameterArray']['fieldConf']['label'] = htmlspecialchars($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:scheduledFrom'));
        $start->data['parameterArray']['itemFormElName'] .= '[start]';
        $start->data['parameterArray']['itemFormElValue'] = DateTimeFactory::createFromTimestamp($executionDetails->getStart() ?: $this->context->getPropertyFromAspect('date', 'timestamp'))->format(DateTimeFormat::ISO8601_LOCALTIME);
        $subFieldResult = $start->render();
        $resultArray['javaScriptModules'] = array_merge($resultArray['javaScriptModules'], $subFieldResult['javaScriptModules']);
        $fieldsHtml .= '<div class="form-group col-sm-6 t3js-timing-options-start">' . $subFieldResult['html'] . '</div>';

        $end = GeneralUtility::makeInstance(DatetimeElement::class);
        $end->data = $this->data;
        $end->data['containerFieldName'] = 'end';
        $end->data['parameterArray']['fieldConf']['label'] = htmlspecialchars($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:scheduledUntil'));
        $end->data['parameterArray']['itemFormElName'] .= '[end]';
        $end->data['parameterArray']['itemFormElValue'] = $executionDetails->getEnd() ? DateTimeFactory::createFromTimestamp($executionDetails->getEnd())->format(DateTimeFormat::ISO8601_LOCALTIME) : null;
        $subFieldResult = $end->render();
        $resultArray['javaScriptModules'] = array_merge($resultArray['javaScriptModules'], $subFieldResult['javaScriptModules']);
        $fieldsHtml .= '<div class="form-group col-sm-6 t3js-timing-options-end">' . $subFieldResult['html'] . '</div>';

        $frequency = GeneralUtility::makeInstance(InputTextElement::class);
        $frequency->data = $this->data;
        $frequency->data['containerFieldName'] = 'frequency';
        $frequency->data['parameterArray']['fieldConf']['label'] = htmlspecialchars($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.frequency.long'));
        $frequency->data['parameterArray']['itemFormElName'] .= '[frequency]';
        $frequency->data['parameterArray']['itemFormElValue'] = $executionDetails->getCronCmd();
        $frequency->data['parameterArray']['fieldChangeFunc'] = [];
        $frequency->data['parameterArray']['fieldConf']['config']['size'] = 40;
        $frequency->data['parameterArray']['fieldConf']['config']['valuePicker'] = [
            'items' => [],
        ];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['frequencyOptions'] ?? [] as $value => $label) {
            $frequency->data['parameterArray']['fieldConf']['config']['valuePicker']['items'][] = [
                'label' => htmlspecialchars($languageService->sL($label)),
                'value' => $value,
            ];
        }
        $subFieldResult = $frequency->render();
        $resultArray['javaScriptModules'] = array_merge($resultArray['javaScriptModules'], $subFieldResult['javaScriptModules']);
        $fieldsHtml .= '<div class="form-group t3js-timing-options-frequency">' . $subFieldResult['html'] . '</div>';

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $html = [];
        $html[] = '<typo3-formengine-element-timing-options class="formengine-field-item t3js-formengine-field-item" fieldPrefix="' . htmlspecialchars($itemName) . '">';
        $html[] =     $fieldInformationHtml;
        $html[] =     '<div class="form-control-wrap" style="max-width: ' . $this->formMaxWidth((int)($this->defaultInputWidth * 1.5)) . 'px">';
        $html[] =         '<div class="form-wizards-wrap">';
        $html[] =             '<div class="form-wizards-item-element">';
        $html[] =                 '<div class="row">' . $fieldsHtml . $this->renderServerTime() . '</div>';
        $html[] =             '</div>';
        $html[] =         '</div>';
        $html[] =     '</div>';
        $html[] = '</typo3-formengine-element-timing-options>';

        $resultArray['html'] = $this->wrapWithFieldsetAndLegend(implode(LF, $html));
        $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create('@typo3/scheduler/form-engine/element/timing-options-element.js');

        return $resultArray;
    }

    protected function renderServerTime(): string
    {
        $view = $this->viewFactory->create(
            new ViewFactoryData(
                templateRootPaths: ['EXT:scheduler/Resources/Private/Templates'],
                partialRootPaths: ['EXT:scheduler/Resources/Private/Partials'],
                layoutRootPaths: ['EXT:scheduler/Resources/Private/Layouts'],
                request: $this->data['request'],
                format: 'html',
            )
        );
        $view->assignMultiple([
            'dateFormat' => [
                'day' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] ?? 'd-m-y',
                'time' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'] ?? 'H:i',
            ],
        ]);
        return $view->render('ServerTime');
    }
}
