<?php
namespace TYPO3\CMS\Linkvalidator\Report;

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

use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Linkvalidator\LinkAnalyzer;

/**
 * Module 'Linkvalidator' for the 'linkvalidator' extension
 */
class LinkValidatorReport extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule
{
    /**
     * @var DocumentTemplate
     */
    public $doc;

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
     * Depth for the recursive traversal of pages for the link validation
     *
     * @var int
     */
    protected $searchLevel;

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
     * List of link types currently chosen in the statistics table
     * Used to show broken links of these types only
     *
     * @var array
     */
    protected $checkOpt = [];

    /**
     * Html for the statistics table with the checkboxes of the link types
     * and the numbers of broken links for report tab
     *
     * @var string
     */
    protected $checkOptionsHtml;

    /**
     * Html for the statistics table with the checkboxes of the link types
     * and the numbers of broken links for check links tab
     *
     * @var string
     */
    protected $checkOptionsHtmlCheck;

    /**
     * Complete content (html) to be displayed
     *
     * @var string
     */
    protected $content;

    /**
     * @var \TYPO3\CMS\Linkvalidator\Linktype\LinktypeInterface[]
     */
    protected $hookObjectsArr = [];

    /**
     * @var string
     */
    protected $updateListHtml = '';

    /**
     * @var string
     */
    protected $refreshListHtml = '';

    /**
     * @var MarkerBasedTemplateService
     */
    protected $templateService;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Main method of modfuncreport
     *
     * @return string Module content
     */
    public function main()
    {
        $this->getLanguageService()->includeLLFile('EXT:linkvalidator/Resources/Private/Language/Module/locallang.xlf');
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->searchLevel = GeneralUtility::_GP('search_levels');
        if (isset($this->pObj->id)) {
            $this->modTS = BackendUtility::getModTSconfig($this->pObj->id, 'mod.linkvalidator');
            $this->modTS = $this->modTS['properties'];
        }
        $update = GeneralUtility::_GP('updateLinkList');
        $prefix = '';
        if (!empty($update)) {
            $prefix = 'check';
        }
        $set = GeneralUtility::_GP($prefix . 'SET');
        $this->pObj->handleExternalFunctionValue();
        if (isset($this->searchLevel)) {
            $this->pObj->MOD_SETTINGS['searchlevel'] = $this->searchLevel;
        } else {
            $this->searchLevel = $this->pObj->MOD_SETTINGS['searchlevel'];
        }
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] as $linkType => $value) {
                // Compile list of all available types. Used for checking with button "Check Links".
                if (strpos($this->modTS['linktypes'], $linkType) !== false) {
                    $this->availableOptions[$linkType] = 1;
                }
                // Compile list of types currently selected by the checkboxes
                if ($this->pObj->MOD_SETTINGS[$linkType] && empty($set) || $set[$linkType]) {
                    $this->checkOpt[$linkType] = 1;
                    $this->pObj->MOD_SETTINGS[$linkType] = 1;
                } else {
                    $this->pObj->MOD_SETTINGS[$linkType] = 0;
                    unset($this->checkOpt[$linkType]);
                }
            }
        }
        $this->getBackendUser()->pushModuleData('web_info', $this->pObj->MOD_SETTINGS);
        $this->initialize();

        // Localization
        $this->getPageRenderer()->addInlineLanguageLabelFile(
            ExtensionManagementUtility::extPath('linkvalidator', 'Resources/Private/Language/Module/locallang.xlf')
        );

        if ($this->modTS['showCheckLinkTab'] == 1) {
            $this->updateListHtml = '<input class="btn btn-default" type="submit" name="updateLinkList" id="updateLinkList" value="' . $this->getLanguageService()->getLL('label_update') . '"/>';
        }
        $this->refreshListHtml = '<input class="btn btn-default" type="submit" name="refreshLinkList" id="refreshLinkList" value="' . $this->getLanguageService()->getLL('label_refresh') . '"/>';
        $this->linkAnalyzer = GeneralUtility::makeInstance(LinkAnalyzer::class);
        $this->updateBrokenLinks();

        $brokenLinkOverView = $this->linkAnalyzer->getLinkCounts($this->pObj->id);
        $this->checkOptionsHtml = $this->getCheckOptions($brokenLinkOverView);
        $this->checkOptionsHtmlCheck = $this->getCheckOptions($brokenLinkOverView, 'check');
        $this->render();

        $pageTile = '';
        if ($this->pObj->id) {
            $pageRecord = BackendUtility::getRecord('pages', $this->pObj->id);
            $pageTile = '<h1>' . htmlspecialchars(BackendUtility::getRecordTitle('pages', $pageRecord)) . '</h1>';
        }

        return '<div id="linkvalidator-modfuncreport">' . $pageTile . $this->createTabs() . '</div>';
    }

    /**
     * Create tabs to split the report and the checkLink functions
     *
     * @return string
     */
    protected function createTabs()
    {
        $menuItems = [
            0 => [
                'label' => $this->getLanguageService()->getLL('Report'),
                'content' => $this->flush(true)
            ],
        ];

        if ((bool)$this->modTS['showCheckLinkTab']) {
            $menuItems[1] = [
                'label' => $this->getLanguageService()->getLL('CheckLink'),
                'content' => $this->flush()
            ];
        }

        // @todo: Use $this-moduleTemplate as soon as this class extends from AbstractModule
        /** @var ModuleTemplate $moduleTemplate */
        $moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        return $moduleTemplate->getDynamicTabMenu($menuItems, 'report-linkvalidator');
    }

    /**
     * Initializes the Module
     *
     * @return void
     */
    protected function initialize()
    {
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] as $linkType => $classRef) {
                $this->hookObjectsArr[$linkType] = GeneralUtility::getUserObj($classRef);
            }
        }

        $this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
        $this->doc->setModuleTemplate('EXT:linkvalidator/Resources/Private/Templates/mod_template.html');

        $this->pageRecord = BackendUtility::readPageAccess($this->pObj->id, $this->getBackendUser()->getPagePermsClause(1));
        if ($this->pObj->id && is_array($this->pageRecord) || !$this->pObj->id && $this->isCurrentUserAdmin()) {
            $this->isAccessibleForCurrentUser = true;
        }

        $this->doc->addStyleSheet('module', ExtensionManagementUtility::extRelPath('linkvalidator') . 'Resources/Public/Css/styles.css');
        $this->getPageRenderer()->loadJquery();
        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Linkvalidator/Linkvalidator');

        $this->templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);

        // Don't access in workspace
        if ($this->getBackendUser()->workspace !== 0) {
            $this->isAccessibleForCurrentUser = false;
        }
    }

    /**
     * Updates the table of stored broken links
     *
     * @return void
     */
    protected function updateBrokenLinks()
    {
        $searchFields = [];
        // Get the searchFields from TypoScript
        foreach ($this->modTS['searchFields.'] as $table => $fieldList) {
            $fields = GeneralUtility::trimExplode(',', $fieldList, true);
            foreach ($fields as $field) {
                if (!$searchFields || !is_array($searchFields[$table]) || array_search($field, $searchFields[$table]) === false) {
                    $searchFields[$table][] = $field;
                }
            }
        }
        $rootLineHidden = $this->linkAnalyzer->getRootLineIsHidden($this->pObj->pageinfo);
        if (!$rootLineHidden || $this->modTS['checkhidden'] == 1) {
            // Get children pages
            $pageList = $this->linkAnalyzer->extGetTreeList(
                $this->pObj->id,
                $this->searchLevel,
                0,
                $this->getBackendUser()->getPagePermsClause(1),
                $this->modTS['checkhidden']
            );
            if ($this->pObj->pageinfo['hidden'] == 0 || $this->modTS['checkhidden']) {
                $pageList .= $this->pObj->id;
            }

            $this->linkAnalyzer->init($searchFields, $pageList, $this->modTS);

            // Check if button press
            $update = GeneralUtility::_GP('updateLinkList');
            if (!empty($update)) {
                $this->linkAnalyzer->getLinkStatistics($this->checkOpt, $this->modTS['checkhidden']);
            }
        }
    }

    /**
     * Renders the content of the module
     *
     * @return void
     */
    protected function render()
    {
        if ($this->isAccessibleForCurrentUser) {
            $this->content = $this->renderBrokenLinksTable();
        } else {
            // If no access or if ID == zero
            /** @var FlashMessage $message */
            $message = GeneralUtility::makeInstance(
                FlashMessage::class,
                $this->getLanguageService()->getLL('no.access'),
                $this->getLanguageService()->getLL('no.access.title'),
                FlashMessage::ERROR
            );
            /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($message);
        }
    }

    /**
     * Flushes the rendered content to the browser
     *
     * @param bool $form
     * @return string $content
     */
    protected function flush($form = false)
    {
        return $this->doc->moduleBody(
            $this->pageRecord,
            $this->getDocHeaderButtons(),
            $form ? $this->getTemplateMarkers() : $this->getTemplateMarkersCheck()
        );
    }

    /**
     * Builds the selector for the level of pages to search
     *
     * @return string Html code of that selector
     */
    protected function getLevelSelector()
    {
        // Build level selector
        $options = [];
        $availableOptions = [
            0 => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_0'),
            1 => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_1'),
            2 => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_2'),
            3 => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_3'),
            4 => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_4'),
            999 => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_infi')
        ];
        foreach ($availableOptions as $optionValue => $optionLabel) {
            $options[] = '<option value="' . $optionValue . '"' . ($optionValue === (int)$this->searchLevel ? ' selected="selected"' : '') . '>' . htmlspecialchars($optionLabel) . '</option>';
        }
        return '<select name="search_levels" class="form-control">' . implode('', $options) . '</select>';
    }

    /**
     * Displays the table of broken links or a note if there were no broken links
     *
     * @return string Content of the table or of the note
     */
    protected function renderBrokenLinksTable()
    {
        $brokenLinkItems = '';
        $brokenLinksTemplate = $this->templateService->getSubpart($this->doc->moduleTemplate, '###NOBROKENLINKS_CONTENT###');
        $keyOpt = [];
        if (is_array($this->checkOpt)) {
            $keyOpt = array_keys($this->checkOpt);
        }

        // Table header
        $brokenLinksMarker = $this->startTable();

        $rootLineHidden = $this->linkAnalyzer->getRootLineIsHidden($this->pObj->pageinfo);
        if (!$rootLineHidden || (bool)$this->modTS['checkhidden']) {
            $pageList = $this->linkAnalyzer->extGetTreeList(
                $this->pObj->id,
                $this->searchLevel,
                0,
                $this->getBackendUser()->getPagePermsClause(1),
                $this->modTS['checkhidden']
            );
            // Always add the current page, because we are just displaying the results
            $pageList .= $this->pObj->id;

            $records = $this->getDatabaseConnection()->exec_SELECTgetRows(
                '*',
                'tx_linkvalidator_link',
                'record_pid IN (' . $pageList . ') AND link_type IN (\'' . implode('\',\'', $keyOpt) . '\')',
                '',
                'record_uid ASC, uid ASC'
            );
            if (!empty($records)) {
                // Display table with broken links
                $brokenLinksTemplate = $this->templateService->getSubpart($this->doc->moduleTemplate, '###BROKENLINKS_CONTENT###');
                $brokenLinksItemTemplate = $this->templateService->getSubpart($this->doc->moduleTemplate, '###BROKENLINKS_ITEM###');

                // Table rows containing the broken links
                $items = [];
                foreach ($records as $record) {
                    $items[] = $this->renderTableRow($record['table_name'], $record, $brokenLinksItemTemplate);
                }
                $brokenLinkItems = implode(LF, $items);
            } else {
                $brokenLinksMarker = $this->getNoBrokenLinkMessage($brokenLinksMarker);
            }
        } else {
            $brokenLinksMarker = $this->getNoBrokenLinkMessage($brokenLinksMarker);
        }
        $brokenLinksTemplate = $this->templateService->substituteMarkerArray(
            $brokenLinksTemplate,
            $brokenLinksMarker, '###|###',
            true
        );
        return $this->templateService->substituteSubpart($brokenLinksTemplate, '###BROKENLINKS_ITEM', $brokenLinkItems);
    }

    /**
     * Replace $brokenLinksMarker['NO_BROKEN_LINKS] with localized flashmessage
     *
     * @param array $brokenLinksMarker
     * @return array $brokenLinksMarker['NO_BROKEN_LINKS] replaced with flashmessage
     */
    protected function getNoBrokenLinkMessage(array $brokenLinksMarker)
    {
        $brokenLinksMarker['LIST_HEADER'] = '<h3>' . $this->getLanguageService()->getLL('list.header', true) . '</h3>';
        /** @var $message FlashMessage */
        $message = GeneralUtility::makeInstance(
            FlashMessage::class,
            $this->getLanguageService()->getLL('list.no.broken.links'),
            $this->getLanguageService()->getLL('list.no.broken.links.title'),
            FlashMessage::OK
        );
        /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($message);
        $brokenLinksMarker['NO_BROKEN_LINKS'] = $defaultFlashMessageQueue->renderFlashMessages();
        return $brokenLinksMarker;
    }

    /**
     * Displays the table header of the table with the broken links
     *
     * @return string Code of content
     */
    protected function startTable()
    {
        // Listing head
        $makerTableHead = [
            'tablehead_path' => $this->getLanguageService()->getLL('list.tableHead.path'),
            'tablehead_element' => $this->getLanguageService()->getLL('list.tableHead.element'),
            'tablehead_headlink' => $this->getLanguageService()->getLL('list.tableHead.headlink'),
            'tablehead_linktarget' => $this->getLanguageService()->getLL('list.tableHead.linktarget'),
            'tablehead_linkmessage' => $this->getLanguageService()->getLL('list.tableHead.linkmessage'),
            'tablehead_lastcheck' => $this->getLanguageService()->getLL('list.tableHead.lastCheck'),
        ];

        // Add CSH to the header of each column
        foreach ($makerTableHead as $column => $label) {
            $makerTableHead[$column] = BackendUtility::wrapInHelp('linkvalidator', $column, $label);
        }
        // Add section header
        $makerTableHead['list_header'] = '<h3>' . $this->getLanguageService()->getLL('list.header', true) . '</h3>';
        return $makerTableHead;
    }

    /**
     * Displays one line of the broken links table
     *
     * @param string $table Name of database table
     * @param array $row Record row to be processed
     * @param array $brokenLinksItemTemplate Markup of the template to be used
     * @return string HTML of the rendered row
     */
    protected function renderTableRow($table, array $row, $brokenLinksItemTemplate)
    {
        $markerArray = [];
        $fieldName = '';
        // Restore the linktype object
        $hookObj = $this->hookObjectsArr[$row['link_type']];

        // Construct link to edit the content element
        $requestUri = GeneralUtility::getIndpEnv('REQUEST_URI') .
            '&id=' . $this->pObj->id .
            '&search_levels=' . $this->searchLevel;
        $url = BackendUtility::getModuleUrl('record_edit', [
            'edit' => [
                $table => [
                    $row['record_uid'] => 'edit'
                ]
            ],
            'returnUrl' => $requestUri
        ]);
        $actionLinkOpen = '<a href="' . htmlspecialchars($url);
        $actionLinkOpen .= '" title="' . htmlspecialchars($this->getLanguageService()->getLL('list.edit')) . '">';
        $actionLinkClose = '</a>';
        $elementHeadline = $row['headline'];
        // Get the language label for the field from TCA
        if ($GLOBALS['TCA'][$table]['columns'][$row['field']]['label']) {
            $fieldName = $this->getLanguageService()->sL($GLOBALS['TCA'][$table]['columns'][$row['field']]['label']);
            // Crop colon from end if present
            if (substr($fieldName, '-1', '1') === ':') {
                $fieldName = substr($fieldName, '0', strlen($fieldName) - 1);
            }
        }
        // Fallback, if there is no label
        $fieldName = !empty($fieldName) ? $fieldName : $row['field'];
        // column "Element"
        $element = '<span title="' . htmlspecialchars($table . ':' . $row['record_uid']) . '">' . $this->iconFactory->getIconForRecord($table, $row, Icon::SIZE_SMALL)->render() . '</span>';
        if (empty($elementHeadline)) {
            $element .= '<i>' . htmlspecialchars($this->getLanguageService()->getLL('list.no.headline')) . '</i>';
        } else {
            $element .= htmlspecialchars($elementHeadline);
        }
        $element .= ' ' . htmlspecialchars(sprintf($this->getLanguageService()->getLL('list.field'), $fieldName));
        $markerArray['actionlinkOpen'] = $actionLinkOpen;
        $markerArray['actionlinkClose'] = $actionLinkClose;
        $markerArray['actionlinkIcon'] = $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render();
        $markerArray['path'] = BackendUtility::getRecordPath($row['record_pid'], '', 0, 0);
        $markerArray['element'] = $element;
        $markerArray['headlink'] = htmlspecialchars($row['link_title']);
        $markerArray['linktarget'] = htmlspecialchars($hookObj->getBrokenUrl($row));
        $response = unserialize($row['url_response']);
        if ($response['valid']) {
            $linkMessage = '<span class="valid">' . htmlspecialchars($this->getLanguageService()->getLL('list.msg.ok')) . '</span>';
        } else {
            $linkMessage = '<span class="error">'
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
        $markerArray['linkmessage'] = $linkMessage;

        $lastRunDate = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], $row['last_check']);
        $lastRunTime = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'], $row['last_check']);
        $markerArray['lastcheck'] = htmlspecialchars(sprintf($this->getLanguageService()->getLL('list.msg.lastRun'), $lastRunDate, $lastRunTime));

        // Return the table html code as string
        return $this->templateService->substituteMarkerArray($brokenLinksItemTemplate, $markerArray, '###|###', true, true);
    }

    /**
     * Builds the checkboxes out of the hooks array
     *
     * @param array $brokenLinkOverView Array of broken links information
     * @param string $prefix
     * @return string code content
     */
    protected function getCheckOptions(array $brokenLinkOverView, $prefix = '')
    {
        $markerArray = [];
        if (!empty($prefix)) {
            $additionalAttr = ' class="' . $prefix . '"';
        } else {
            $additionalAttr = ' class="refresh"';
        }
        $checkOptionsTemplate = $this->templateService->getSubpart($this->doc->moduleTemplate, '###CHECKOPTIONS_SECTION###');
        $hookSectionTemplate = $this->templateService->getSubpart($checkOptionsTemplate, '###HOOK_SECTION###');
        $markerArray['statistics_header'] = '<h3>' . $this->getLanguageService()->getLL('report.statistics.header', true) . '</h3>';
        $markerArray['total_count_label'] = BackendUtility::wrapInHelp('linkvalidator', 'checkboxes', $this->getLanguageService()->getLL('overviews.nbtotal'));
        $markerArray['total_count'] = $brokenLinkOverView['brokenlinkCount'] ?: '0';

        $linktypes = GeneralUtility::trimExplode(',', $this->modTS['linktypes'], true);
        $hookSectionContent = '';
        if (is_array($linktypes)) {
            if (
                !empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])
                && is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])
            ) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] as $type => $value) {
                    if (in_array($type, $linktypes)) {
                        $hookSectionMarker = [
                            'count' => $brokenLinkOverView[$type] ?: '0',
                        ];

                        $translation = $this->getLanguageService()->getLL('hooks.' . $type) ?: $type;
                        $hookSectionMarker['option'] = '<input type="checkbox"' . $additionalAttr . ' id="' . $prefix . 'SET_' . $type . '" name="' . $prefix
                            . 'SET[' . $type . ']" value="1"' . ($this->pObj->MOD_SETTINGS[$type] ? ' checked="checked"' : '') . '/>' . '<label for="'
                            . $prefix . 'SET_' . $type . '">&nbsp;' . htmlspecialchars($translation) . '</label>';

                        $hookSectionContent .= $this->templateService->substituteMarkerArray(
                            $hookSectionTemplate,
                            $hookSectionMarker, '###|###',
                            true,
                            true
                        );
                    }
                }
            }
        }
        $checkOptionsTemplate = $this->templateService->substituteSubpart(
            $checkOptionsTemplate,
            '###HOOK_SECTION###',
            $hookSectionContent
        );
        return $this->templateService->substituteMarkerArray($checkOptionsTemplate, $markerArray, '###|###', true, true);
    }

    /**
     * Gets the buttons that shall be rendered in the docHeader
     *
     * @return array Available buttons for the docHeader
     */
    protected function getDocHeaderButtons()
    {
        return [
            'csh' => BackendUtility::cshItem('_MOD_web_func', ''),
            'shortcut' => $this->getShortcutButton(),
            'save' => ''
        ];
    }

    /**
     * Gets the button to set a new shortcut in the backend (if current user is allowed to).
     *
     * @return string HTML representation of the shortcut button
     */
    protected function getShortcutButton()
    {
        $result = '';
        if ($this->getBackendUser()->mayMakeShortcut()) {
            $result = $this->doc->makeShortcutIcon('', 'function', $this->pObj->MCONF['name']);
        }
        return $result;
    }

    /**
     * Gets the filled markers that are used in the HTML template
     *
     * @return array The filled marker array
     */
    protected function getTemplateMarkers()
    {
        return [
            'FUNC_TITLE' => $this->getLanguageService()->getLL('report.func.title'),
            'CHECKOPTIONS_TITLE' => $this->getLanguageService()->getLL('report.statistics.header'),
            'FUNC_MENU' => $this->getLevelSelector(),
            'CONTENT' => $this->content,
            'CHECKOPTIONS' => $this->checkOptionsHtml,
            'ID' => '<input type="hidden" name="id" value="' . $this->pObj->id . '" />',
            'REFRESH' => '<input type="submit" class="btn btn-default" name="refreshLinkList" id="refreshLinkList" value="' . $this->getLanguageService()->getLL('label_refresh') . '" />',
            'UPDATE' => '',
        ];
    }

    /**
     * Gets the filled markers that are used in the HTML template
     *
     * @return array The filled marker array
     */
    protected function getTemplateMarkersCheck()
    {
        return [
            'FUNC_TITLE' => $this->getLanguageService()->getLL('checklinks.func.title'),
            'CHECKOPTIONS_TITLE' => $this->getLanguageService()->getLL('checklinks.statistics.header'),
            'FUNC_MENU' => $this->getLevelSelector(),
            'CONTENT' => '',
            'CHECKOPTIONS' => $this->checkOptionsHtmlCheck,
            'ID' => '<input type="hidden" name="id" value="' . $this->pObj->id . '" />',
            'REFRESH' => '',
            'UPDATE' => '<input type="submit" class="btn btn-default" name="updateLinkList" id="updateLinkList" value="' . $this->getLanguageService()->getLL('label_update') . '"/>',
        ];
    }

    /**
     * Determines whether the current user is an admin
     *
     * @return bool Whether the current user is admin
     */
    protected function isCurrentUserAdmin()
    {
        return $this->getBackendUser()->isAdmin();
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
