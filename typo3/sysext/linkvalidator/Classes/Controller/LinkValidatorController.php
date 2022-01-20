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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\BackendTemplateView;
use TYPO3\CMS\Linkvalidator\LinkAnalyzer;
use TYPO3\CMS\Linkvalidator\Linktype\LinktypeInterface;
use TYPO3\CMS\Linkvalidator\Repository\BrokenLinkRepository;
use TYPO3\CMS\Linkvalidator\Repository\PagesRepository;

/**
 * Controller for the LinkValidator report module
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
class LinkValidatorController
{
    /**
     * Information about the current page record
     */
    protected array $pageRecord = [];

    /**
     * Link validation class
     */
    protected LinkAnalyzer $linkAnalyzer;

    /**
     * TSconfig of the current module
     */
    protected array $modTS = [];

    /**
     * Depth for the recursive traversal of pages for the link validation
     * For "Report" and "Check link" tab.
     */
    protected array $searchLevel = ['report' => 0, 'check' => 0];

    /**
     * List of link types currently chosen in the statistics table
     * Used to show broken links of these types only
     * For "Report" and "Check link" tab
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

    /**
     * @var LinktypeInterface[]
     */
    protected array $hookObjectsArr = [];

    protected int $id;
    protected array $searchFields = [];

    protected ServerRequestInterface $request;

    public function __construct(
        protected Context $context,
        protected UriBuilder $uriBuilder,
        protected IconFactory $iconFactory,
        protected PagesRepository $pagesRepository,
        protected BrokenLinkRepository $brokenLinkRepository,
        protected ModuleTemplateFactory $moduleTemplateFactory
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $this->request = $request;
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->id = (int)($this->request->getQueryParams()['id'] ?? 0);
        $this->modTS = BackendUtility::getPagesTSconfig($this->id)['mod.']['linkvalidator.'] ?? [];

        // Get the current page record
        $this->pageRecord = BackendUtility::readPageAccess($this->id, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW)) ?: [];
        if ($this->pageRecord !== []) {
            $moduleTemplate->getDocHeaderComponent()->setMetaInformation($this->pageRecord);
        }

        // Get the hook objects
        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] ?? [] as $linkType => $className) {
            $hookObject = GeneralUtility::makeInstance($className);
            if (!$hookObject instanceof LinktypeInterface) {
                continue;
            }
            $this->hookObjectsArr[$linkType] = $hookObject;
        }

        $this->getLanguageService()->includeLLFile('EXT:linkvalidator/Resources/Private/Language/Module/locallang.xlf');
        $this->validateSettings();
        $this->initializeLinkAnalyzer();

        if ($this->request->getParsedBody()['updateLinkList'] ?? false) {
            $this->updateBrokenLinks();
        } elseif ($this->lastEditedRecord['uid']) {
            if (($this->modTS['actionAfterEditRecord'] ?? '') === 'recheck') {
                // recheck broken links for last edited reccord
                $this->linkAnalyzer->recheckLinks(
                    $this->checkOpt['check'],
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

        $moduleTemplate->setTitle($this->getModuleTitle());

        $content = $this->createModuleContent($moduleTemplate);
        return new HtmlResponse($moduleTemplate->setContent($content)->renderContent());
    }

    /**
     * Create tabs to split the report and the checkLink functions
     */
    protected function createModuleContent(ModuleTemplate $moduleTemplate): string
    {
        if ($this->getBackendUser()->workspace !== 0
            || !(($this->id && $this->pageRecord !== []) || (!$this->id && $this->getBackendUser()->isAdmin()))
        ) {
            $moduleTemplate->addFlashMessage(
                $this->getLanguageService()->getLL('no.access'),
                $this->getLanguageService()->getLL('no.access.title'),
                FlashMessage::ERROR
            );
            return '';
        }

        $action = $this->request->getQueryParams()['action'] ?? null;
        $moduleData = $this->getBackendUser()->getModuleData('web_linkvalidator') ?? [];
        if (in_array($action, ['report', 'check'], true)) {
            $moduleData['action'] = $action;
            $this->getBackendUser()->pushModuleData('web_linkvalidator', $moduleData);
        } else {
            $action = $moduleData['action'] ?? 'report';
        }

        $languageService = $this->getLanguageService();
        $view = GeneralUtility::makeInstance(BackendTemplateView::class);
        $view->setTemplateRootPaths(['EXT:linkvalidator/Resources/Private/Templates']);
        $view->setPartialRootPaths(['EXT:linkvalidator/Resources/Private/Partials']);
        if ($action === 'report') {
            $content = $view->assignMultiple([
                'title' => $this->pageRecord ? BackendUtility::getRecordTitle('pages', $this->pageRecord) : '',
                'prefix' => 'report',
                'selectedLevel' => $this->searchLevel['report'],
                'options' => $this->getCheckOptions('report'),
                'brokenLinks' => $this->getBrokenLinks(),
                'tableheadPath' => BackendUtility::wrapInHelp('linkvalidator', 'tablehead_path', $languageService->getLL('list.tableHead.path')),
                'tableheadElement' => BackendUtility::wrapInHelp('linkvalidator', 'tablehead_element', $languageService->getLL('list.tableHead.element')),
                'tableheadHeadlink' => BackendUtility::wrapInHelp('linkvalidator', 'tablehead_headlink', $languageService->getLL('list.tableHead.headlink')),
                'tableheadLinktarget' => BackendUtility::wrapInHelp('linkvalidator', 'tablehead_linktarget', $languageService->getLL('list.tableHead.linktarget')),
                'tableheadLinkmessage' => BackendUtility::wrapInHelp('linkvalidator', 'tablehead_linkmessage', $languageService->getLL('list.tableHead.linkmessage')),
                'tableheadLastcheck' => BackendUtility::wrapInHelp('linkvalidator', 'tablehead_lastcheck', $languageService->getLL('list.tableHead.lastCheck')),
            ])->render('Backend/Report');
        } elseif ($action === 'check') {
            $content = $view->assignMultiple([
                'title' => $this->pageRecord ? BackendUtility::getRecordTitle('pages', $this->pageRecord) : '',
                'prefix' => 'check',
                'selectedLevel' => $this->searchLevel['check'],
                'options' => $this->getCheckOptions('check'),
            ])->render('Backend/CheckLinks');
        } else {
            $content = '';
        }

        $this->registerDocHeaderButtons($moduleTemplate, $action);

        // In case user is not allowed to change the tab (action), return content and skip action menu generation
        if (!($this->modTS['showCheckLinkTab'] ?? false)) {
            return $content;
        }

        $actions = [
            'report' => [
                'label' => $languageService->getLL('Report'),
                'href' => $this->getModuleUri('report'),
                'active' => $action === 'report',
            ],
            'check' => [
                'label' => $languageService->getLL('CheckLink'),
                'href' => $this->getModuleUri('check'),
                'active' => $action === 'check',
            ],
        ];

        $actionMenu = $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $actionMenu->setIdentifier('reportLinkvalidatorSelector');
        foreach ($actions as $action) {
            $menuItem = $actionMenu
                ->makeMenuItem()
                ->setTitle($action['label'])
                ->setHref($action['href']);
            if ($action['active']) {
                $menuItem->setActive(true);
            }
            $actionMenu->addMenuItem($menuItem);
        }
        $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($actionMenu);

        return $content;
    }

    /**
     * Checks for incoming GET/POST parameters to update the module settings
     */
    protected function validateSettings(): void
    {
        $prefix = 'check';
        $other = 'report';
        if (empty($this->request->getParsedBody()['updateLinkList'] ?? false)) {
            $prefix = 'report';
            $other = 'check';
        }

        // get linkvalidator module data
        $moduleData = $this->getBackendUser()->getModuleData('web_linkvalidator');

        // get information for last edited record
        $this->lastEditedRecord['uid'] = $this->request->getQueryParams()['last_edited_record_uid'] ?? 0;
        $this->lastEditedRecord['table'] = $this->request->getQueryParams()['last_edited_record_table'] ?? '';
        $this->lastEditedRecord['field'] = $this->request->getQueryParams()['last_edited_record_field'] ?? '';
        $this->lastEditedRecord['timestamp'] = $this->request->getQueryParams()['last_edited_record_timestamp'] ?? 0;

        // get searchLevel (number of levels of pages to check / show results)
        $this->searchLevel[$prefix] = $this->request->getQueryParams()[$prefix . '_search_levels'] ?? $this->request->getParsedBody()[$prefix . '_search_levels'] ?? null;

        $mainSearchLevelKey = $prefix . '_searchlevel';
        $otherSearchLevelKey = $other . '_searchlevel';
        if ($this->searchLevel[$prefix] !== null) {
            $moduleData[$mainSearchLevelKey] = $this->searchLevel[$prefix];
        } else {
            $this->searchLevel[$prefix] = $moduleData[$mainSearchLevelKey] ?? 0;
        }
        if (isset($moduleData[$otherSearchLevelKey])) {
            $this->searchLevel[$other] = $moduleData[$otherSearchLevelKey] ?? 0;
        }

        // which linkTypes to check (internal, file, external, ...)
        $set = $this->request->getParsedBody()[$prefix . '_SET'] ?? [];
        $submittedValues = $this->request->getParsedBody()[$prefix . '_values'] ?? [];

        foreach (array_keys($this->hookObjectsArr) as $linkType) {
            // Compile list of all available types. Used for checking with button "Check Links".
            unset($this->checkOpt[$prefix][$linkType]);
            $mainLinkType = $prefix . '_' . $linkType;
            $otherLinkType = $other . '_' . $linkType;

            // 1) if "$prefix_values" = "1" : use POST variables
            // 2) if not set, use stored module configuration
            // 3) if not set, use default
            if (!empty($submittedValues)) {
                $this->checkOpt[$prefix][$linkType] = $set[$linkType] ?? '0';
                $moduleData[$mainLinkType] = $this->checkOpt[$prefix][$linkType];
            } elseif (isset($moduleData[$mainLinkType])) {
                $this->checkOpt[$prefix][$linkType] = $moduleData[$mainLinkType];
            } else {
                // use default
                $this->checkOpt[$prefix][$linkType] = '0';
                $moduleData[$mainLinkType] = $this->checkOpt[$prefix][$linkType];
            }

            if (isset($moduleData[$otherLinkType])) {
                $this->checkOpt[$other][$linkType] = $moduleData[$otherLinkType];
            }
        }

        // save settings
        $this->getBackendUser()->pushModuleData('web_linkvalidator', $moduleData);
    }

    /**
     * Updates the table of stored broken links
     */
    protected function initializeLinkAnalyzer(): void
    {
        $this->linkAnalyzer = GeneralUtility::makeInstance(LinkAnalyzer::class);
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
     * Check for broken links
     */
    protected function updateBrokenLinks(): void
    {
        // convert ['external' => 1, 'db' => 0, ...] into ['external']
        $linkTypes = [];
        foreach ($this->checkOpt['check'] ?? [] as $linkType => $value) {
            if ($value) {
                $linkTypes[] = $linkType;
            }
        }
        $this->linkAnalyzer->getLinkStatistics($linkTypes, (bool)($this->modTS['checkhidden'] ?? false));
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
                $items[] = $this->generateTableRow($row);
            }
        }
        if (empty($items)) {
            $this->createFlashMessagesForNoBrokenLinks();
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
        $checkForHiddenPages = (bool)($this->modTS['checkhidden'] ?? false);
        $permsClause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
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
     * Used when there are no broken links found.
     */
    protected function createFlashMessagesForNoBrokenLinks(): void
    {
        $message = GeneralUtility::makeInstance(
            FlashMessage::class,
            $this->getLanguageService()->getLL('list.no.broken.links'),
            $this->getLanguageService()->getLL('list.no.broken.links.title'),
            FlashMessage::OK,
            false
        );
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier('linkvalidator');
        $defaultFlashMessageQueue->enqueue($message);
    }

    /**
     * Generates information for a single row of the broken links table
     */
    protected function generateTableRow(array $row): array
    {
        $fieldLabel = $row['field'];
        $table = $row['table_name'];
        $languageService = $this->getLanguageService();
        $hookObj = $this->hookObjectsArr[$row['link_type'] ?? ''];

        // Try to resolve the field label from TCA
        if ($GLOBALS['TCA'][$table]['columns'][$row['field']]['label'] ?? false) {
            $fieldLabel = $languageService->sL($GLOBALS['TCA'][$table]['columns'][$row['field']]['label']);
            // Crop colon from end if present
            if (str_ends_with($fieldLabel, ':')) {
                $fieldLabel = substr($fieldLabel, 0, -1);
            }
        }

        return [
            'title' => $table . ':' . $row['record_uid'],
            'icon' => $this->iconFactory->getIconForRecord($table, $row, Icon::SIZE_SMALL)->render(),
            'headline' => $row['headline'],
            'label' => sprintf($languageService->getLL('list.field'), $fieldLabel),
            'path' => BackendUtility::getRecordPath($row['record_pid'], $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW), 0),
            'linkTitle' => $row['link_title'],
            'linkTarget' => $hookObj->getBrokenUrl($row),
            'linkStatus' => (bool)($row['url_response']['valid'] ?? false),
            'linkMessage' => $hookObj->getErrorMessage($row['url_response']['errorParams']),
            'lastCheck' => sprintf(
                $languageService->getLL('list.msg.lastRun'),
                date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], $row['last_check']),
                date($GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'], $row['last_check'])
            ),
            'needsRecheck' => (bool)$row['needs_recheck'],
            // Construct link to edit the record
            'editUrl' => (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                'edit' => [
                    $table => [
                        $row['record_uid'] => 'edit',
                    ],
                ],
                'columnsOnly' => $row['field'],
                'returnUrl' => $this->getModuleUri(
                    'report',
                    [
                        'last_edited_record_uid' => $row['record_uid'],
                        'last_edited_record_table' => $table,
                        'last_edited_record_field' => $row['field'],
                        'last_edited_record_timestamp' => $this->context->getPropertyFromAspect('date', 'timestamp'),
                    ]
                ),
            ]),
        ];
    }

    /**
     * Builds the checkboxes to show which types of links are available
     *
     * @param string $prefix "report" or "check" for "Report" and "Check links" tab
     * @return array
     */
    protected function getCheckOptions(string $prefix): array
    {
        $brokenLinksInformation = $this->linkAnalyzer->getLinkCounts();
        $options = [
            'totalCountLabel' => BackendUtility::wrapInHelp('linkvalidator', 'checkboxes', $this->getLanguageService()->getLL('overviews.nbtotal')),
            'totalCount' => $brokenLinksInformation['total'] ?: '0',
            'optionsByType' => [],
        ];
        $linkTypes = GeneralUtility::trimExplode(',', $this->modTS['linktypes'] ?? '', true);
        $availableLinkTypes = array_keys($this->hookObjectsArr);
        foreach ($availableLinkTypes as $type) {
            if (!in_array($type, $linkTypes, true)) {
                continue;
            }
            $options['optionsByType'][$type] = [
                'id' => $prefix . '_SET_' . $type,
                'name' => $prefix . '_SET[' . $type . ']',
                'label' => $this->getLanguageService()->getLL('hooks.' . $type) ?: $type,
                'checked' => !empty($this->checkOpt[$prefix][$type]) ? ' checked="checked"' : '',
                'count' => (!empty($brokenLinksInformation[$type]) ? $brokenLinksInformation[$type] : '0'),
            ];
        }
        return $options;
    }

    protected function registerDocHeaderButtons(ModuleTemplate $moduleTemplate, string $action): void
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('web_linkvalidator')
            ->setDisplayName($this->getModuleTitle())
            ->setArguments(['id' => $this->id, 'action' => $action]);
        $buttonBar->addButton($shortcutButton);
    }

    protected function getModuleUri(string $action = null, array $additionalPramaters = []): string
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
        $pageTitle = '';
        $moduleName = $this->getLanguageService()->sL('LLL:EXT:linkvalidator/Resources/Private/Language/Module/locallang_mod.xlf:mlang_labels_tablabel');
        if ($this->id === 0) {
            $pageTitle = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        } elseif ($this->pageRecord !== []) {
            $pageTitle = BackendUtility::getRecordTitle('pages', $this->pageRecord, false, false);
        }
        return $moduleName . ($pageTitle !== '' ? ': ' . $pageTitle : '');
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
