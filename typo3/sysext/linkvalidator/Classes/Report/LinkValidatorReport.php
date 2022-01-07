<?php

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

namespace TYPO3\CMS\Linkvalidator\Report;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Info\Controller\InfoModuleController;
use TYPO3\CMS\Linkvalidator\LinkAnalyzer;
use TYPO3\CMS\Linkvalidator\Linktype\LinktypeInterface;
use TYPO3\CMS\Linkvalidator\Repository\BrokenLinkRepository;
use TYPO3\CMS\Linkvalidator\Repository\PagesRepository;

/**
 * Module 'LinkValidator' as sub module of Web -> Info
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
class LinkValidatorReport
{
    /**
     * Information about the current page record
     *
     * @var array
     */
    protected $pageRecord = [];

    /**
     * Information, if the module is accessible for the current user or not
     *
     * @var bool
     */
    protected $isAccessibleForCurrentUser = false;

    /**
     * Link validation class
     *
     * @var LinkAnalyzer
     */
    protected $linkAnalyzer;

    /**
     * TSconfig of the current module
     *
     * @var array
     */
    protected $modTS = [];

    /**
     * List of available link types to check defined in the TSconfig
     *
     * @var array
     */
    protected $availableOptions = [];

    /**
     * Depth for the recursive traversal of pages for the link validation
     * For "Report" and "Check link" tab.
     *
     * @var array
     */
    protected $searchLevel = ['report' => 0, 'check' => 0];

    /**
     * List of link types currently chosen in the statistics table
     * Used to show broken links of these types only
     * For "Report" and "Check link" tab
     *
     * @var array
     */
    protected $checkOpt = ['report' => [], 'check' => []];

    /**
     * Information for last edited record
     * @var array
     */
    protected $lastEditedRecord = [
        'uid'   => 0,
        'table' => '',
        'field' => '',
        'timestamp' => 0,
    ];

    /**
     * @var LinktypeInterface[]
     */
    protected $hookObjectsArr = [];

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var int Value of the GET/POST var 'id'
     */
    protected $id;

    /**
     * @var InfoModuleController Contains a reference to the parent calling object
     */
    protected $pObj;

    /**
     * @var array
     */
    protected $searchFields = [];

    /**
     * @var BrokenLinkRepository
     */
    protected $brokenLinkRepository;

    /**
     * @var PagesRepository
     */
    protected $pagesRepository;

    /**
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * @var StandaloneView
     */
    protected $view;

    protected ModuleTemplateFactory $moduleTemplateFactory;
    protected PageRenderer $pageRenderer;

    public function __construct(
        PagesRepository $pagesRepository = null,
        BrokenLinkRepository $brokenLinkRepository = null,
        ModuleTemplateFactory $moduleTemplateFactory = null,
        IconFactory $iconFactory = null,
        PageRenderer $pageRecord = null
    ) {
        $this->iconFactory = $iconFactory ?? GeneralUtility::makeInstance(IconFactory::class);
        $this->pagesRepository = $pagesRepository ?? GeneralUtility::makeInstance(PagesRepository::class);
        $this->moduleTemplateFactory = $moduleTemplateFactory ?? GeneralUtility::makeInstance(
            ModuleTemplateFactory::class,
            GeneralUtility::makeInstance(PageRenderer::class),
            $this->iconFactory,
            GeneralUtility::makeInstance(FlashMessageService::class)
        );
        $this->brokenLinkRepository = $brokenLinkRepository ??
            GeneralUtility::makeInstance(BrokenLinkRepository::class);
        $this->pageRenderer = $pageRecord ??
            GeneralUtility::makeInstance(PageRenderer::class);
    }

    /**
     * Init, called from parent object
     *
     * @param InfoModuleController $pObj A reference to the parent (calling) object
     */
    public function init($pObj, ServerRequestInterface $request)
    {
        $this->pObj = $pObj;
        $this->id = (int)($request->getParsedBody()['id'] ?? $request->getQueryParams()['id'] ?? 0);
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $this->view = $this->createView('InfoModule');
    }

    protected function createView(string $templateName): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths(['EXT:linkvalidator/Resources/Private/Layouts']);
        $view->setPartialRootPaths(['EXT:linkvalidator/Resources/Private/Partials']);
        $view->setTemplateRootPaths(['EXT:linkvalidator/Resources/Private/Templates/Backend']);
        $view->setTemplate($templateName);
        $view->assign('pageId', $this->id);
        return $view;
    }

    /**
     * Checks for incoming GET/POST parameters to update the module settings
     */
    protected function validateSettings(ServerRequestInterface $request)
    {
        $prefix = 'check';
        $other = 'report';
        if (empty($request->getParsedBody()['updateLinkList'] ?? false)) {
            $prefix = 'report';
            $other = 'check';
        }

        // get information for last edited record
        $this->lastEditedRecord['uid'] = $request->getQueryParams()['last_edited_record_uid'] ?? 0;
        $this->lastEditedRecord['table'] = $request->getQueryParams()['last_edited_record_table'] ?? '';
        $this->lastEditedRecord['field'] = $request->getQueryParams()['last_edited_record_field'] ?? '';
        $this->lastEditedRecord['timestamp'] = $request->getQueryParams()['last_edited_record_timestamp'] ?? 0;

        // get searchLevel (number of levels of pages to check / show results)
        $this->searchLevel[$prefix] = $request->getParsedBody()[$prefix . '_search_levels'] ?? $request->getQueryParams()[$prefix . '_search_levels'] ?? null;
        if ($this->searchLevel[$prefix] !== null) {
            $this->pObj->MOD_SETTINGS[$prefix . '_searchlevel'] = $this->searchLevel[$prefix];
        } else {
            $this->searchLevel[$prefix] = $this->pObj->MOD_SETTINGS[$prefix . '_searchlevel'] ?? 0;
        }
        if (isset($this->pObj->MOD_SETTINGS[$other . '_searchlevel'])) {
            $this->searchLevel[$other] = $this->pObj->MOD_SETTINGS[$other . '_searchlevel'] ?? 0;
        }

        // which linkTypes to check (internal, file, external, ...)
        $set = $request->getParsedBody()[$prefix . '_SET'] ?? $request->getQueryParams()[$prefix . '_SET'] ?? [];
        $submittedValues = $request->getParsedBody()[$prefix . '_values'] ?? [];

        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] ?? [] as $linkType => $value) {
            // Compile list of all available types. Used for checking with button "Check Links".
            if (str_contains($this->modTS['linktypes'], $linkType)) {
                $this->availableOptions[$linkType] = 1;
            }

            // 1) if "$prefix_values" = "1" : use POST variables
            // 2) if not set, use stored configuration in $this->>pObj->MOD_SETTINGS
            // 3) if not set, use default
            unset($this->checkOpt[$prefix][$linkType]);
            if (!empty($submittedValues)) {
                $this->checkOpt[$prefix][$linkType] = $set[$linkType] ?? '0';
                $this->pObj->MOD_SETTINGS[$prefix . '_' . $linkType] = $this->checkOpt[$prefix][$linkType];
            } elseif (isset($this->pObj->MOD_SETTINGS[$prefix . '_' . $linkType])) {
                $this->checkOpt[$prefix][$linkType] = $this->pObj->MOD_SETTINGS[$prefix . '_' . $linkType];
            } else {
                // use default
                $this->checkOpt[$prefix][$linkType] = '0';
                $this->pObj->MOD_SETTINGS[$prefix . '_' . $linkType] = $this->checkOpt[$prefix][$linkType];
            }
            if (isset($this->pObj->MOD_SETTINGS[$other . '_' . $linkType])) {
                $this->checkOpt[$other][$linkType] = $this->pObj->MOD_SETTINGS[$other . '_' . $linkType];
            }
        }

        // save settings
        $this->getBackendUser()->pushModuleData('web_info', $this->pObj->MOD_SETTINGS);
    }
    /**
     * Main, called from parent object
     *
     * @return string Module content
     */
    public function main(ServerRequestInterface $request)
    {
        $this->getLanguageService()->includeLLFile('EXT:linkvalidator/Resources/Private/Language/Module/locallang.xlf');
        if (isset($this->id)) {
            $this->modTS = BackendUtility::getPagesTSconfig($this->id)['mod.']['linkvalidator.'] ?? [];
        }
        $this->validateSettings($request);
        $this->initialize();

        if ($request->getParsedBody()['updateLinkList'] ?? false) {
            $this->updateBrokenLinks();
        } elseif ($this->lastEditedRecord['uid']) {
            if ($this->modTS['actionAfterEditRecord'] === 'recheck') {
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

        $pageTitle = $this->pageRecord ? BackendUtility::getRecordTitle('pages', $this->pageRecord) : '';
        $this->view->assign('title', $pageTitle);
        $this->view->assign('content', $this->renderContent());
        return $this->view->render();
    }

    /**
     * Create tabs to split the report and the checkLink functions
     */
    protected function renderContent(): string
    {
        if (!$this->isAccessibleForCurrentUser) {
            // If no access or if ID == zero
            $this->moduleTemplate->addFlashMessage(
                $this->getLanguageService()->getLL('no.access'),
                $this->getLanguageService()->getLL('no.access.title'),
                FlashMessage::ERROR
            );
            return '';
        }

        $groupedBrokenLinkCounts = $this->linkAnalyzer->getLinkCounts();

        $reportsTabView = $this->createViewForBrokenLinksTab($groupedBrokenLinkCounts);
        $menuItems = [
            0 => [
                'label' => $this->getLanguageService()->getLL('Report'),
                'content' => $reportsTabView->render(),
            ],
        ];

        if ((bool)$this->modTS['showCheckLinkTab']) {
            $reportsTabView = $this->createView('CheckLinksTab');
            $reportsTabView->assignMultiple([
                'prefix' => 'check',
                'selectedLevel' => $this->searchLevel['check'],
                'options' => $this->getCheckOptions($groupedBrokenLinkCounts, 'check'),
            ]);
            $menuItems[1] = [
                'label' => $this->getLanguageService()->getLL('CheckLink'),
                'content' => $reportsTabView->render(),
            ];
        }
        return $this->moduleTemplate->getDynamicTabMenu($menuItems, 'report-linkvalidator');
    }

    /**
     * Initializes the Module
     */
    protected function initialize()
    {
        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] ?? [] as $linkType => $className) {
            $hookObject = GeneralUtility::makeInstance($className);
            if (!$hookObject instanceof LinktypeInterface) {
                continue;
            }
            $this->hookObjectsArr[$linkType] = $hookObject;
        }

        $this->pageRecord = BackendUtility::readPageAccess($this->id, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW)) ?: [];
        if (($this->id && $this->pageRecord !== []) || (!$this->id && $this->getBackendUser()->isAdmin())) {
            $this->isAccessibleForCurrentUser = true;
        }
        // Don't access in workspace
        if ($this->getBackendUser()->workspace !== 0) {
            $this->isAccessibleForCurrentUser = false;
        }

        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Linkvalidator/Linkvalidator');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:linkvalidator/Resources/Private/Language/Module/locallang.xlf');

        $this->initializeLinkAnalyzer();
    }

    /**
     * Updates the table of stored broken links
     */
    protected function initializeLinkAnalyzer()
    {
        $this->linkAnalyzer = GeneralUtility::makeInstance(LinkAnalyzer::class);
        // Get the searchFields from TSconfig
        foreach ($this->modTS['searchFields.'] as $table => $fieldList) {
            $fields = GeneralUtility::trimExplode(',', $fieldList, true);
            foreach ($fields as $field) {
                if (!$this->searchFields || !is_array($this->searchFields[$table] ?? null) || !in_array(
                    $field,
                    $this->searchFields[$table],
                    true
                )) {
                    $this->searchFields[$table][] = $field;
                }
            }
        }

        $rootLineHidden = $this->pagesRepository->doesRootLineContainHiddenPages($this->pObj->pageinfo);
        if (!$rootLineHidden || ($this->modTS['checkhidden'] ?? 0) == 1) {
            $this->linkAnalyzer->init(
                $this->searchFields,
                $this->getPageList(),
                $this->modTS
            );
        }
    }

    /**
     * Check for broken links
     */
    protected function updateBrokenLinks()
    {
        // convert ['external' => 1, 'db' => 0, ...] into ['external']
        $linkTypes = [];
        foreach ($this->checkOpt['check'] ?? [] as $linkType => $value) {
            if ($value) {
                $linkTypes[] = $linkType;
            }
        }
        $this->linkAnalyzer->getLinkStatistics($linkTypes, $this->modTS['checkhidden']);
    }

    /**
     * Displays the table of broken links or a note if there were no broken links
     *
     * @param array $amountOfBrokenLinks
     * @return StandaloneView
     */
    protected function createViewForBrokenLinksTab(array $amountOfBrokenLinks)
    {
        $view = $this->createView('ReportTab');
        $view->assignMultiple([
            'prefix' => 'report',
            'selectedLevel' => $this->searchLevel['report'],
            'options' => $this->getCheckOptions($amountOfBrokenLinks, 'report'),
        ]);
        // Table header
        $view->assignMultiple($this->getVariablesForTableHeader());

        $linkTypes = [];
        if (is_array($this->checkOpt['report'])) {
            $linkTypes = array_keys($this->checkOpt['report'], '1');
        }
        $items = [];
        $rootLineHidden = $this->pagesRepository->doesRootLineContainHiddenPages($this->pObj->pageinfo);
        if (!$rootLineHidden || (bool)$this->modTS['checkhidden'] && !empty($linkTypes)) {
            $brokenLinks = $this->brokenLinkRepository->getAllBrokenLinksForPages(
                $this->getPageList(),
                $linkTypes,
                $this->searchFields
            );
            foreach ($brokenLinks as $row) {
                $items[] = $this->renderTableRow($row['table_name'], $row);
            }
        }
        if (empty($items)) {
            $this->createFlashMessagesForNoBrokenLinks();
        }
        $view->assign('brokenLinks', $items);
        return $view;
    }

    /**
     * Generates an array of page uids from current pageUid.
     * List does include pageUid itself.
     *
     * @return int[]
     */
    protected function getPageList(): array
    {
        $checkForHiddenPages = (bool)$this->modTS['checkhidden'];
        $permsClause = (string)$this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
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
     * Sets variables for the Fluid Template of the table with the broken links
     * @return array variables
     */
    protected function getVariablesForTableHeader(): array
    {
        $languageService = $this->getLanguageService();
        $variables = [
            'tableheadPath' => $languageService->getLL('list.tableHead.path'),
            'tableheadElement' => $languageService->getLL('list.tableHead.element'),
            'tableheadHeadlink' => $languageService->getLL('list.tableHead.headlink'),
            'tableheadLinktarget' => $languageService->getLL('list.tableHead.linktarget'),
            'tableheadLinkmessage' => $languageService->getLL('list.tableHead.linkmessage'),
            'tableheadLastcheck' => $languageService->getLL('list.tableHead.lastCheck'),
        ];

        // Add CSH to the header of each column
        foreach ($variables as $column => $label) {
            $variables[$column] = BackendUtility::wrapInHelp('linkvalidator', $column, $label);
        }
        return $variables;
    }

    /**
     * Displays one line of the broken links table
     *
     * @param string $table Name of database table
     * @param array $row Record row to be processed
     * @return array HTML of the rendered row
     */
    protected function renderTableRow($table, array $row)
    {
        $languageService = $this->getLanguageService();
        $variables = [];
        $fieldName = '';
        // Restore the linktype object
        $hookObj = $this->hookObjectsArr[$row['link_type']];

        // Construct link to edit the content element
        $requestUri = $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri() .
            '&id=' . $this->id .
            '&search_levels=' . $this->searchLevel['report'] .
            // add record_uid as query parameter for rechecking after edit
            '&last_edited_record_uid=' . $row['record_uid'] .
            '&last_edited_record_table=' . $row['table_name'] .
            '&last_edited_record_field=' . $row['field'] .
            '&last_edited_record_timestamp=' . $GLOBALS['EXEC_TIME'];

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $url = (string)$uriBuilder->buildUriFromRoute('record_edit', [
            'edit' => [
                $table => [
                    $row['record_uid'] => 'edit',
                ],
            ],
            'columnsOnly' => $row['field'],
            'returnUrl' => $requestUri,
        ]);
        $variables['editUrl'] = $url;
        // Get the language label for the field from TCA
        if ($GLOBALS['TCA'][$table]['columns'][$row['field']]['label']) {
            $fieldName = $languageService->sL($GLOBALS['TCA'][$table]['columns'][$row['field']]['label']);
            // Crop colon from end if present
            if (substr($fieldName, -1, 1) === ':') {
                $fieldName = substr($fieldName, 0, strlen($fieldName) - 1);
            }
        }
        // Add element information
        $variables['element'] = '
            <span title="' . htmlspecialchars($table . ':' . $row['record_uid']) . '">
                ' . $this->iconFactory->getIconForRecord($table, $row, Icon::SIZE_SMALL)->render() . '
            </span>
            ' . (($row['headline'] ?? false) ? htmlspecialchars($row['headline']) : '<i>' . htmlspecialchars($languageService->getLL('list.no.headline')) . '</i>') . '
            ' . htmlspecialchars(sprintf($languageService->getLL('list.field'), (!empty($fieldName) ? $fieldName : $row['field'])));
        $variables['path'] = BackendUtility::getRecordPath($row['record_pid'], $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW), 0);
        $variables['link_title'] = $row['link_title'];
        $variables['linktarget'] = $hookObj->getBrokenUrl($row);
        $response = $row['url_response'];
        if ($response['valid']) {
            $linkMessage = '<span class="text-success">' . htmlspecialchars($languageService->getLL('list.msg.ok')) . '</span>';
        } else {
            $linkMessage = '<span class="text-danger">'
                . nl2br(
                // Encode for output
                    htmlspecialchars(
                        $hookObj->getErrorMessage($response['errorParams']),
                        ENT_QUOTES,
                        'UTF-8',
                        false
                    )
                )
                . '</span>';
        }
        $variables['linkmessage'] = $linkMessage;
        $lastRunDate = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], $row['last_check']);
        $lastRunTime = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'], $row['last_check']);
        $variables['lastcheck'] = htmlspecialchars(sprintf($languageService->getLL('list.msg.lastRun'), $lastRunDate, $lastRunTime));
        $variables['needs_recheck'] = (bool)$row['needs_recheck'];
        return $variables;
    }

    /**
     * Builds the checkboxes to show which types of links are available
     *
     * @param array $brokenLinkOverView Array of broken links information
     * @param string $prefix "report" or "check" for "Report" and "Check links" tab
     * @return array
     */
    protected function getCheckOptions(array $brokenLinkOverView, $prefix)
    {
        $variables = [];
        $variables['totalCountLabel'] = BackendUtility::wrapInHelp('linkvalidator', 'checkboxes', $this->getLanguageService()->getLL('overviews.nbtotal'));
        $variables['totalCount'] = $brokenLinkOverView['total'] ?: '0';
        $variables['optionsByType'] = [];
        $linkTypes = GeneralUtility::trimExplode(',', $this->modTS['linktypes'] ?? '', true);
        $availableLinkTypes = array_keys($this->hookObjectsArr);
        foreach ($availableLinkTypes as $type) {
            if (!in_array($type, $linkTypes, true)) {
                continue;
            }
            $label = $this->getLanguageService()->getLL('hooks.' . $type) ?: $type;
            $id = $prefix . '_SET_' . $type;
            $checked = !empty($this->checkOpt[$prefix][$type]) ? ' checked="checked"' : '';
            $variables['optionsByType'][$type] = [
                'count' => (!empty($brokenLinkOverView[$type]) ? $brokenLinkOverView[$type] : '0'),
                'checkbox' => '<input type="checkbox" class="form-check-input mt-1" value="1" id="' . $id . '" name="' . $prefix . '_SET[' . $type . ']"' . $checked . '/>',
                'label' => '<label class="form-check-label lh-lg" for="' . $id . '">' . htmlspecialchars($label) . '</label>',
            ];
        }
        return $variables;
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
