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

namespace TYPO3\CMS\Linkvalidator\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Linkvalidator\LinkAnalyzer;
use TYPO3\CMS\Linkvalidator\Linktype\LabelledLinktypeInterface;
use TYPO3\CMS\Linkvalidator\Linktype\LinktypeRegistry;
use TYPO3\CMS\Linkvalidator\Repository\BrokenLinkRepository;
use TYPO3\CMS\Linkvalidator\Repository\PagesRepository;

/**
 * Controller for the LinkValidator report module
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
#[AsController]
class LinkValidatorController
{
    /**
     * Information about the current page record
     */
    protected array $pageRecord = [];

    /**
     * TSconfig of the current module
     */
    protected array $modTS = [];

    /**
     * Depth for the recursive traversal of pages for the link validation
     * For "Report" and "Check link" form
     */
    protected array $searchLevel = ['report' => 0, 'check' => 0];

    /**
     * List of link types currently chosen in the statistics table
     * Used to show broken links of these types only
     * For "Report" and "Check link" form
     */
    protected array $checkOpt = ['report' => [], 'check' => []];

    /**
     * Information for last edited record
     */
    protected array $lastEditedRecord = [
        'uid'   => 0,
        'table' => '',
        'field' => '',
        'timestamp' => 0,
    ];

    protected int $id;
    protected array $searchFields = [];

    public function __construct(
        protected readonly Context $context,
        protected readonly UriBuilder $uriBuilder,
        protected readonly IconFactory $iconFactory,
        protected readonly PagesRepository $pagesRepository,
        protected readonly BrokenLinkRepository $brokenLinkRepository,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly LinkAnalyzer $linkAnalyzer,
        protected readonly LinktypeRegistry $linktypeRegistry,
        protected readonly TranslationConfigurationProvider $translationConfigurationProvider,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $backendUser = $this->getBackendUser();
        $languageService = $this->getLanguageService();

        $this->id = (int)($request->getQueryParams()['id'] ?? 0);
        $this->modTS = BackendUtility::getPagesTSconfig($this->id)['mod.']['linkvalidator.'] ?? [];
        $this->pageRecord = BackendUtility::readPageAccess($this->id, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW)) ?: [];

        $view = $this->moduleTemplateFactory->create($request);
        if ($this->pageRecord !== []) {
            $view->getDocHeaderComponent()->setMetaInformation($this->pageRecord);
        }

        $this->validateSettings($request);
        $this->initializeLinkAnalyzer();

        if ($request->getParsedBody()['updateLinkList'] ?? false) {
            $this->updateBrokenLinks();
        } elseif ($this->lastEditedRecord['uid']) {
            if (($this->modTS['actionAfterEditRecord'] ?? '') === 'recheck') {
                // recheck broken links for last edited record
                $this->linkAnalyzer->recheckLinks(
                    $this->getLinkTypesFromCheckOptions(),
                    $this->lastEditedRecord['uid'],
                    $this->lastEditedRecord['table'],
                    $this->lastEditedRecord['field'],
                    (int)$this->lastEditedRecord['timestamp']
                );
            } else {
                // mark broken links for last edited record as needing a recheck
                $this->brokenLinkRepository->setNeedsRecheckForRecord(
                    (int)$this->lastEditedRecord['uid'],
                    $this->lastEditedRecord['table']
                );
            }
        }

        $view->setTitle($this->getModuleTitle());

        if ($backendUser->workspace !== 0 || !(($this->id && $this->pageRecord !== []) || (!$this->id && $backendUser->isAdmin()))) {
            $view->addFlashMessage($languageService->sL('LLL:EXT:linkvalidator/Resources/Private/Language/Module/locallang.xlf:no.access'), $languageService->sL('LLL:EXT:linkvalidator/Resources/Private/Language/Module/locallang.xlf:no.access.title'), ContextualFeedbackSeverity::ERROR);
            return $view->renderResponse('Backend/Empty');
        }

        $moduleData = $request->getAttribute('moduleData');
        if (!($this->modTS['showCheckLinkTab'] ?? false)) {
            $moduleData->set('action', 'report');
            $backendUser->pushModuleData($moduleData->getModuleIdentifier(), $moduleData->toArray());
        } elseif ($moduleData->clean('action', ['report', 'check'])) {
            $backendUser->pushModuleData($moduleData->getModuleIdentifier(), $moduleData->toArray());
        }
        $action = $moduleData->get('action');

        $this->addDocHeaderShortCutButton($view, $action);

        $checkFormEnabled = false;
        if (($this->modTS['showCheckLinkTab'] ?? '') === '1') {
            $checkFormEnabled = true;
        }

        $brokenLinksInformation = $this->linkAnalyzer->getLinkCounts();

        $view->assignMultiple([
            'pageUid' => $this->id,
            'pageTitle' => $this->pageRecord ? BackendUtility::getRecordTitle('pages', $this->pageRecord) : '',
            'checkFormEnabled' => $checkFormEnabled,
            'selectedLevelCheck' => $this->searchLevel['check'],
            'selectedLevelReport' => $this->searchLevel['report'],
            'optionsCheck' => $this->getCheckOptions('check'),
            'optionsReport' => $this->getCheckOptions('report'),
            'brokenLinks' => $this->getBrokenLinks(),
            'brokenLinkTotalCount' => $brokenLinksInformation['total'] ?: '0',
        ]);
        return $view->renderResponse('Backend/Report');
    }

    /**
     * Checks for incoming GET/POST parameters to update the module settings
     */
    protected function validateSettings(ServerRequestInterface $request): void
    {
        $backendUser = $this->getBackendUser();

        $prefix = 'check';
        $other = 'report';
        if (empty($request->getParsedBody()['updateLinkList'] ?? false)) {
            $prefix = 'report';
            $other = 'check';
        }

        // get linkvalidator module data
        $moduleData = $request->getAttribute('moduleData');

        // get information for last edited record
        $this->lastEditedRecord['uid'] = $request->getQueryParams()['last_edited_record_uid'] ?? 0;
        $this->lastEditedRecord['table'] = $request->getQueryParams()['last_edited_record_table'] ?? '';
        $this->lastEditedRecord['field'] = $request->getQueryParams()['last_edited_record_field'] ?? '';
        $this->lastEditedRecord['timestamp'] = $request->getQueryParams()['last_edited_record_timestamp'] ?? 0;

        // get searchLevel (number of levels of pages to check / show results)
        $this->searchLevel[$prefix] = $request->getQueryParams()[$prefix . '_search_levels'] ?? $request->getParsedBody()[$prefix . '_search_levels'] ?? null;

        $mainSearchLevelKey = $prefix . '_searchlevel';
        $otherSearchLevelKey = $other . '_searchlevel';
        if ($this->searchLevel[$prefix] !== null) {
            $moduleData->set($mainSearchLevelKey, $this->searchLevel[$prefix]);
        } else {
            $this->searchLevel[$prefix] = $moduleData->get($mainSearchLevelKey, 0);
        }
        if ($moduleData->has($otherSearchLevelKey)) {
            $this->searchLevel[$other] = $moduleData->get($otherSearchLevelKey);
        }

        // which linkTypes to check (internal, file, external, ...)
        $set = $request->getParsedBody()[$prefix . '_SET'] ?? [];
        $submittedValues = $request->getParsedBody()[$prefix . '_values'] ?? [];

        foreach ($this->linktypeRegistry->getIdentifiers() as $linkType) {
            // Compile list of all available types. Used for checking with button "Check Links".
            unset($this->checkOpt[$prefix][$linkType]);
            $mainLinkType = $prefix . '_' . $linkType;
            $otherLinkType = $other . '_' . $linkType;

            // 1) if "$prefix_values" = "1" : use POST variables
            // 2) if not set, use stored module configuration
            // 3) if not set, use default
            if (!empty($submittedValues)) {
                $this->checkOpt[$prefix][$linkType] = $set[$linkType] ?? '0';
                $moduleData->set($mainLinkType, $this->checkOpt[$prefix][$linkType]);
            } elseif ($moduleData->has($mainLinkType)) {
                $this->checkOpt[$prefix][$linkType] = $moduleData->get($mainLinkType);
            } else {
                // use default
                $this->checkOpt[$prefix][$linkType] = '0';
                $moduleData->set($mainLinkType, $this->checkOpt[$prefix][$linkType]);
            }

            if ($moduleData->has($otherLinkType)) {
                $this->checkOpt[$other][$linkType] = $moduleData->get($otherLinkType);
            }
        }

        // save settings
        $backendUser->pushModuleData($moduleData->getModuleIdentifier(), $moduleData->toArray());
    }

    /**
     * Updates the table of stored broken links
     */
    protected function initializeLinkAnalyzer(): void
    {
        // Get the searchFields from TSconfig
        foreach ($this->modTS['searchFields.'] ?? [] as $table => $fieldList) {
            $fields = GeneralUtility::trimExplode(',', $fieldList, true);
            foreach ($fields as $field) {
                if (!$this->searchFields
                    || !is_array($this->searchFields[$table] ?? null)
                    || !in_array($field, $this->searchFields[$table], true)
                ) {
                    $this->searchFields[$table][] = $field;
                }
            }
        }
        $rootLineHidden = $this->pagesRepository->doesRootLineContainHiddenPages($this->pageRecord);
        if (!$rootLineHidden || ($this->modTS['checkhidden'] ?? false)) {
            $this->linkAnalyzer->init($this->searchFields, $this->getPageList(), $this->modTS);
        }
    }

    /**
     * @return string[]
     */
    protected function getLinkTypesFromCheckOptions(): array
    {
        // convert ['external' => 1, 'db' => 0, ...] into ['external']
        $linkTypes = [];
        foreach ($this->checkOpt['check'] ?? [] as $linkType => $value) {
            if ($value) {
                $linkTypes[] = $linkType;
            }
        }
        return $linkTypes;
    }

    /**
     * Check for broken links
     */
    protected function updateBrokenLinks(): void
    {
        $this->linkAnalyzer->getLinkStatistics(
            $this->getLinkTypesFromCheckOptions(),
            (bool)($this->modTS['checkhidden'] ?? false)
        );
    }

    /**
     * Returns the broken links or adds a note if there were no broken links
     */
    protected function getBrokenLinks(): array
    {
        $items = [];
        $linkTypes = [];
        if (is_array($this->checkOpt['report'])) {
            $linkTypes = array_keys($this->checkOpt['report'], '1');
        }
        $rootLineHidden = $this->pagesRepository->doesRootLineContainHiddenPages($this->pageRecord);
        if (!empty($linkTypes) && (!$rootLineHidden || ($this->modTS['checkhidden'] ?? false))) {
            $brokenLinks = $this->brokenLinkRepository->getAllBrokenLinksForPages(
                $this->getPageList(),
                $linkTypes,
                $this->searchFields
            );
            foreach ($brokenLinks as $row) {
                if (!$this->tcaSchemaFactory->has($row['table_name'])) {
                    continue;
                }
                $items[] = $this->generateTableRow($row);
            }
        }
        return $items;
    }

    /**
     * Generates an array of page uids from current pageUid.
     * List does include pageUid itself.
     *
     * @return int[]
     */
    protected function getPageList(): array
    {
        $backendUser = $this->getBackendUser();
        $checkForHiddenPages = (bool)($this->modTS['checkhidden'] ?? false);
        $permsClause = $backendUser->getPagePermsClause(Permission::PAGE_SHOW);
        $pageList = $this->pagesRepository->getAllSubpagesForPage(
            $this->id,
            (int)$this->searchLevel['report'],
            $permsClause,
            $checkForHiddenPages
        );
        // Always add the current page, because we are just displaying the results
        $pageList[] = $this->id;
        $pageTranslations = $this->pagesRepository->getTranslationForPage(
            $this->id,
            $permsClause,
            $checkForHiddenPages
        );
        return array_merge($pageList, $pageTranslations);
    }

    /**
     * Generates information for a single row of the broken links table
     */
    protected function generateTableRow(array $row): array
    {
        $table = $row['table_name'];
        $elementType = $row['element_type'] ?? null;
        $schema = $this->tcaSchemaFactory->get($table);
        $languageService = $this->getLanguageService();
        $linkType = $this->linktypeRegistry->getLinktype($row['link_type'] ?? '');

        // Try to resolve the field label from TCA
        if ($schema->hasSubSchema($elementType) && $schema->getSubSchema($elementType)->hasField($row['field'])) {
            $fieldLabel = $schema->getSubSchema($elementType)->getField($row['field'])->getLabel();
        } else {
            $fieldLabel = $schema->getField($row['field'])->getLabel();
        }
        // Crop colon from end if present
        $fieldLabel = rtrim((string)($fieldLabel ?: $row['field']), ':');

        $result = [
            'uid' => $row['uid'],
            'recordUid' => $row['record_uid'],
            'recordTable' => $table,
            'recordTableTitle' => $languageService->sL($schema->getRawConfiguration()['title'] ?? ''),
            // @todo: Remove this assignment (and template use) when linkvalidator stops rendering broken
            //        links registered to records that are meanwhile deleted=1 or in a different workspace.
            'recordTableIconDefault' => $this->iconFactory->getIconForRecord($table, $row, IconSize::SMALL)->render(),
            'recordFieldLabel' => $languageService->sL($fieldLabel),
            'recordTitle' => $row['headline'],
            'recordLanguageIcon' => $this->iconFactory->getIcon($this->getSystemLanguageValue($row['language'], $row['record_pid'], 'flagIcon'), IconSize::SMALL)->getIdentifier(),
            'recordLanguageTitle' => $this->getSystemLanguageValue($row['language'], $row['record_pid'], 'title'),
            'backendUserTitleLength' => (int)$this->getBackendUser()->uc['titleLen'],
            'recordData' => BackendUtility::getRecord($table, abs((int)$row['record_uid'])),
            'recordPageData' => BackendUtility::getRecord('pages', abs((int)$row['record_pid'])),
            'linkType' => $row['link_type'],
            'linkText' => $row['link_title'],
            'linkTarget' => $linkType?->getBrokenUrl($row),
            'linkErrorMessage' => $linkType?->getErrorMessage($row['url_response']['errorParams']),
            'lastCheck' => sprintf(
                $languageService->sL('LLL:EXT:linkvalidator/Resources/Private/Language/Module/locallang.xlf:list.msg.lastRun'),
                date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], $row['last_check']),
                date($GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'], $row['last_check'])
            ),
            'needsRecheck' => (bool)$row['needs_recheck'],
        ];
        $editUrlParameters = [
            'edit' => [
                $table => [
                    $row['record_uid'] => 'edit',
                ],
            ],
            'returnUrl' => $this->getModuleUri(
                'report',
                [
                    'last_edited_record_uid' => $row['record_uid'],
                    'last_edited_record_table' => $table,
                    'last_edited_record_field' => $row['field'],
                    'last_edited_record_timestamp' => $this->context->getPropertyFromAspect('date', 'timestamp'),
                ]
            ),
        ];
        $result['editUrlFull'] = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $editUrlParameters);
        $result['editUrlField'] = (string)$this->uriBuilder->buildUriFromRoute('record_edit', array_merge($editUrlParameters, ['columnsOnly' => [$table => [$row['field']]]]));
        return $result;
    }

    /**
     * Gets a named value of an available system language
     *
     * @param int $id system language uid
     * @param int $pageId page id of a site
     * @param string $key Name of the value to be fetched (e.g. title)
     */
    protected function getSystemLanguageValue(int $id, int $pageId, string $key): string
    {
        $value = '';
        $systemLanguages = $this->translationConfigurationProvider->getSystemLanguages($pageId);
        if (!empty($systemLanguages[$id][$key])) {
            $value = $systemLanguages[$id][$key];
        }
        return $value;
    }

    /**
     * Builds the checkboxes to show which types of links are available
     *
     * @param string $prefix "report" or "check" for "Report" and "Check links" form
     */
    protected function getCheckOptions(string $prefix): array
    {
        $brokenLinksInformation = $this->linkAnalyzer->getLinkCounts();
        $options = [
            'optionsByType' => [],
        ];
        $linkTypes = GeneralUtility::trimExplode(',', $this->modTS['linktypes'] ?? '', true);
        foreach ($this->linktypeRegistry->getIdentifiers() as $type) {
            if (!in_array($type, $linkTypes, true)) {
                continue;
            }
            $isChecked = !empty($this->checkOpt[$prefix][$type]);
            $linkType = $this->linktypeRegistry->getLinktype($type);
            $linktypeLabel = ($linkType instanceof LabelledLinktypeInterface)
                ? ($linkType->getReadableName() ?: $linkType->getIdentifier())
                : $type;
            $options['optionsByType'][$type] = [
                'id' => $prefix . '_SET_' . $type,
                'name' => $prefix . '_SET[' . $type . ']',
                'label' => $linktypeLabel,
                'checked' => $isChecked,
                'count' => (!empty($brokenLinksInformation[$type]) ? $brokenLinksInformation[$type] : '0'),
            ];
        }
        $options['allOptionsChecked'] = array_filter($options['optionsByType'], static fn(array $option): bool => !$option['checked']) === [];
        return $options;
    }

    protected function addDocHeaderShortCutButton(ModuleTemplate $view, string $action): void
    {
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('web_linkvalidator')
            ->setDisplayName($this->getModuleTitle())
            ->setArguments(['id' => $this->id, 'action' => $action]);
        $buttonBar->addButton($shortcutButton);
    }

    protected function getModuleUri(?string $action = null, array $additionalPramaters = []): string
    {
        $parameters = [
            'id' => $this->id,
        ];
        if ($action !== null) {
            $parameters['action'] = $action;
        }
        return (string)$this->uriBuilder->buildUriFromRoute('web_linkvalidator', array_replace($parameters, $additionalPramaters));
    }

    protected function getModuleTitle(): string
    {
        $languageService = $this->getLanguageService();
        $pageTitle = '';
        $moduleName = $languageService->sL('LLL:EXT:linkvalidator/Resources/Private/Language/Module/locallang_mod.xlf:mlang_labels_tablabel');
        if ($this->id === 0) {
            $pageTitle = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        } elseif ($this->pageRecord !== []) {
            $pageTitle = BackendUtility::getRecordTitle('pages', $this->pageRecord, false, false);
        }
        return $moduleName . ($pageTitle !== '' ? ': ' . $pageTitle : '');
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
