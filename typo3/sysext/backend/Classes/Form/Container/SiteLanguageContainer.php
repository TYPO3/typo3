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

namespace TYPO3\CMS\Backend\Form\Container;

use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Site languages entry container
 *
 * @internal This container is only used in the site configuration module and is not public API
 */
class SiteLanguageContainer extends AbstractContainer
{
    private const FOREIGN_TABLE = 'site_language';
    private const FOREIGN_FIELD = 'languageId';

    protected array $inlineData;
    protected InlineStackProcessor $inlineStackProcessor;

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

    /**
     * Container objects give $nodeFactory down to other containers.
     *
     * @param NodeFactory $nodeFactory
     * @param array $data
     */
    public function __construct(NodeFactory $nodeFactory, array $data)
    {
        parent::__construct($nodeFactory, $data);
        $this->inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
    }

    public function render(): array
    {
        $this->inlineData = $this->data['inlineData'];

        $this->inlineStackProcessor->initializeByGivenStructure($this->data['inlineStructure']);

        $row = $this->data['databaseRow'];
        $parameterArray = $this->data['parameterArray'];
        $config = $parameterArray['fieldConf']['config'];
        $resultArray = $this->initializeResultArray();

        // Add the current inline job to the structure stack
        $this->inlineStackProcessor->pushStableStructureItem([
            'table' => $this->data['tableName'],
            'uid' => $row['uid'],
            'field' => $this->data['fieldName'],
            'config' => $config,
        ]);

        // Hand over original returnUrl to SiteInlineAjaxController. Needed if opening for instance a
        // nested element in a new view to then go back to the original returnUrl and not the url of
        // the site inline ajax controller.
        $config['originalReturnUrl'] = $this->data['returnUrl'];

        // e.g. data[site][1][languages]
        $nameForm = $this->inlineStackProcessor->getCurrentStructureFormPrefix();
        // e.g. data-0-site-1-languages
        $nameObject = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']);
        // e.g. array('table' => 'site', 'uid' => '1', 'field' => 'languages', 'config' => array())
        $top = $this->inlineStackProcessor->getStructureLevel(0);

        $this->inlineData['config'][$nameObject] = [
            'table' => self::FOREIGN_TABLE,
        ];

        $configJson = (string)json_encode($config);
        $this->inlineData['config'][$nameObject . '-' . self::FOREIGN_TABLE] = [
            'min' => $config['minitems'],
            'max' => $config['maxitems'],
            'sortable' => false,
            'top' => [
                'table' => $top['table'],
                'uid' => $top['uid'],
            ],
            'context' => [
                'config' => $configJson,
                'hmac' => GeneralUtility::hmac($configJson, 'InlineContext'),
            ],
        ];
        $this->inlineData['nested'][$nameObject] = $this->data['tabAndInlineStack'];

        $uniqueIds = [];
        foreach ($parameterArray['fieldConf']['children'] as $children) {
            $value = (int)($children['databaseRow'][self::FOREIGN_FIELD]['0'] ?? 0);
            if (isset($children['databaseRow']['uid'])) {
                $uniqueIds[$children['databaseRow']['uid']] = $value;
            }
        }

        $uniquePossibleRecords = $config['uniquePossibleRecords'] ?? [];
        $possibleRecordsUidToTitle = [];
        foreach ($uniquePossibleRecords as $possibleRecord) {
            $possibleRecordsUidToTitle[$possibleRecord[1]] = $possibleRecord[0];
        }
        $this->inlineData['unique'][$nameObject . '-' . self::FOREIGN_TABLE] = [
            // Usually "max" would the the number of possible records. However, since
            // we also allow new languages to be created, we just use the maxitems value.
            'max' => $config['maxitems'],
            // "used" must be a string array
            'used' => array_map('strval', $uniqueIds),
            'table' => self::FOREIGN_TABLE,
            'elTable' => self::FOREIGN_TABLE,
            'field' => self::FOREIGN_FIELD,
            'possible' => $possibleRecordsUidToTitle,
        ];

        $resultArray['inlineData'] = $this->inlineData;

        $fieldInformationResult = $this->renderFieldInformation();
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);
        $selectorOptions = $childRecordUids = $childHtml = [];

        foreach ($config['uniquePossibleRecords'] ?? [] as $record) {
            // Do not add the PHP_INT_MAX placeholder or already configured languages
            if ($record[1] !== PHP_INT_MAX && !in_array($record[1], $uniqueIds, true)) {
                $selectorOptions[] = ['value' => (string)$record[1], 'label' => (string)$record[0]];
            }
        }

        foreach ($this->data['parameterArray']['fieldConf']['children'] as $children) {
            $children['inlineParentUid'] = $row['uid'];
            $children['inlineFirstPid'] = $this->data['inlineFirstPid'];
            $children['inlineParentConfig'] = $config;
            $children['inlineData'] = $this->inlineData;
            $children['inlineStructure'] = $this->inlineStackProcessor->getStructure();
            $children['inlineExpandCollapseStateArray'] = $this->data['inlineExpandCollapseStateArray'];
            $children['renderType'] = 'inlineRecordContainer';
            $childResult = $this->nodeFactory->create($children)->render();
            $childHtml[] = $childResult['html'];
            $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $childResult, false);
            if (isset($children['databaseRow']['uid'])) {
                $childRecordUids[] = $children['databaseRow']['uid'];
            }
        }

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
            'EXT:backend/Resources/Private/Templates/SiteLanguageContainer.html'
        ));
        $view->assignMultiple([
            'nameObject' => $nameObject,
            'nameForm' => $nameForm,
            'formGroupAttributes' => GeneralUtility::implodeAttributes([
                'class' => 'form-group',
                'id' => $nameObject,
                'data-uid' => (string)$row['uid'],
                'data-local-table' => (string)$top['table'],
                'data-local-field' => (string)$top['field'],
                'data-foreign-table' => self::FOREIGN_TABLE,
                'data-object-group' => $nameObject . '-' . self::FOREIGN_TABLE,
                'data-form-field' => $nameForm,
                'data-appearance' => (string)json_encode($config['appearance'] ?? ''),
            ], true),
            'fieldInformation' => $fieldInformationResult['html'],
            'selectorConfigutation' => [
                'identifier' => $nameObject . '-' . self::FOREIGN_TABLE . '_selector',
                'size' => $config['size'] ?? 4,
                'options' =>  $selectorOptions,
            ],
            'inlineRecords' => [
                'identifier' => $nameObject . '_records',
                'title' => trim($parameterArray['fieldConf']['label'] ?? ''),
                'records' => implode(PHP_EOL, $childHtml),
            ],
            'childRecordUids' => implode(',', $childRecordUids),
            'validationRules' => $this->getValidationDataAsJsonString([
                'type' => 'inline',
                'minitems' => $config['minitems'] ?? null,
                'maxitems' => $config['maxitems'] ?? null,
            ]),
        ]);

        $resultArray['html'] = $view->render();
        $resultArray['requireJsModules'][] = JavaScriptModuleInstruction::forRequireJS('TYPO3/CMS/Backend/FormEngine/Container/SiteLanguageContainer');

        return $resultArray;
    }
}
