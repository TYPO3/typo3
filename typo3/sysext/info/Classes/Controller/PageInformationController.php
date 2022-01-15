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

namespace TYPO3\CMS\Info\Controller;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class for displaying page information (records, page record properties) in Web -> Info
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
class PageInformationController
{
    /**
     * @var array
     */
    protected $fieldConfiguration = [];

    /**
     * @var int Value of the GET/POST var 'id'
     */
    protected $id;

    /**
     * @var InfoModuleController Contains a reference to the parent calling object
     */
    protected $pObj;

    protected IconFactory $iconFactory;
    protected UriBuilder $uriBuilder;
    protected ?BackendLayoutView $backendLayoutView = null;

    /**
     * @var array
     */
    protected $fieldArray;

    /**
     * Keys are fieldnames and values are td-css-classes to add in addElement();
     *
     * @var array
     */
    protected $addElement_tdCssClass = [];

    public function __construct(IconFactory $iconFactory, UriBuilder $uriBuilder)
    {
        $this->iconFactory = $iconFactory;
        $this->uriBuilder = $uriBuilder;
    }

    /**
     * Init, called from parent object
     *
     * @param InfoModuleController $pObj A reference to the parent (calling) object
     * @param ServerRequestInterface $request
     */
    public function init(InfoModuleController $pObj, ServerRequestInterface $request)
    {
        $this->pObj = $pObj;
        $this->id = (int)($request->getParsedBody()['id'] ?? $request->getQueryParams()['id'] ?? 0);
        // Setting MOD_MENU items as we need them for logging:
        $this->pObj->MOD_MENU = array_merge($this->pObj->MOD_MENU, $this->modMenu());
    }

    /**
     * Main, called from parent object
     *
     * @param ServerRequestInterface $request
     * @return string Output HTML for the module.
     */
    public function main(ServerRequestInterface $request)
    {
        $theOutput = '<h1>' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:page_title')) . '</h1>';

        if (isset($this->fieldConfiguration[$this->pObj->MOD_SETTINGS['pages']])) {
            $this->fieldArray = $this->fieldConfiguration[$this->pObj->MOD_SETTINGS['pages']]['fields'];
        }

        $theOutput .= '
        <div class="row row-cols-auto mb-3 g-3 align-items-center">
            <div class="col">
                <label class="form-lable">' .
                    htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:moduleFunctions.depth')) .
                '</label> ' .
                BackendUtility::getDropdownMenu($this->id, 'SET[depth]', $this->pObj->MOD_SETTINGS['depth'], $this->pObj->MOD_MENU['depth']) .
            '</div>
            <div class="col">
                <label class="form-lable">' .
                    htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:moduleFunctions.type')) .
                '</label> ' .
                BackendUtility::getDropdownMenu($this->id, 'SET[pages]', $this->pObj->MOD_SETTINGS['pages'], $this->pObj->MOD_MENU['pages']) .
            '</div>' .
            BackendUtility::cshItem('_MOD_web_info', 'func_' . $this->pObj->MOD_SETTINGS['pages'], '', '<div class="col"><span class="btn btn-default btn-sm">|</span></div>') .
        '</div>'
            . $this->getTable_pages($this->id, (int)$this->pObj->MOD_SETTINGS['depth'], $request);

        // Additional footer content
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/web_info/class.tx_cms_webinfo.php']['drawFooterHook'] ?? [] as $hook) {
            $params = [
                'request' => $request,
            ];
            $theOutput .= GeneralUtility::callUserFunction($hook, $params, $this);
        }
        return $theOutput;
    }

    /**
     * Returns the menu array
     *
     * @return array
     */
    protected function modMenu()
    {
        $menu = [
            'pages' => [],
            'depth' => [
                0 => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_0'),
                1 => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_1'),
                2 => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_2'),
                3 => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_3'),
                4 => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_4'),
                999 => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_infi'),
            ],
        ];

        $this->fillFieldConfiguration($this->id);
        foreach ($this->fieldConfiguration as $key => $item) {
            $menu['pages'][$key] = $item['label'];
        }
        return $menu;
    }

    /**
     * Function, which returns all tables to
     * which the user has access. Also a set of standard tables (pages, sys_filemounts, etc...)
     * are filtered out. So what is left is basically all tables which makes sense to list content from.
     *
     * @return string
     */
    protected function cleanTableNames(): string
    {
        // Get all table names:
        $tableNames = array_flip(array_keys($GLOBALS['TCA']));
        // Unset common names:
        unset($tableNames['pages']);
        unset($tableNames['sys_filemounts']);
        unset($tableNames['sys_action']);
        unset($tableNames['sys_workflows']);
        unset($tableNames['be_users']);
        unset($tableNames['be_groups']);
        $allowedTableNames = [];
        // Traverse table names and set them in allowedTableNames array IF they can be read-accessed by the user.
        if (is_array($tableNames)) {
            foreach ($tableNames as $k => $v) {
                if (!($GLOBALS['TCA'][$k]['ctrl']['hideTable'] ?? false) && $this->getBackendUser()->check('tables_select', $k)) {
                    $allowedTableNames['table_' . $k] = $k;
                }
            }
        }
        return implode(',', array_keys($allowedTableNames));
    }

    /**
     * Generate configuration for field selection
     *
     * @param int $pageId current page id
     */
    protected function fillFieldConfiguration(int $pageId)
    {
        $modTSconfig = BackendUtility::getPagesTSconfig($pageId)['mod.']['web_info.']['fieldDefinitions.'] ?? [];
        foreach ($modTSconfig as $key => $item) {
            $fieldList = str_replace('###ALL_TABLES###', $this->cleanTableNames(), $item['fields']);
            $fields = GeneralUtility::trimExplode(',', $fieldList, true);
            $key = trim($key, '.');
            $this->fieldConfiguration[$key] = [
                'label' => $item['label'] ? $this->getLanguageService()->sL($item['label']) : $key,
                'fields' => $fields,
            ];
        }
    }

    /**
     * Renders records from the pages table from page id
     *
     * @param int $id Page id
     * @param int $depth
     * @param ServerRequestInterface $request
     * @return string HTML for the listing
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function getTable_pages($id, int $depth, ServerRequestInterface $request)
    {
        $out = '';
        $lang = $this->getLanguageService();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $row = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)),
                $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW)
            )
            ->executeQuery()
            ->fetchAssociative();
        BackendUtility::workspaceOL('pages', $row);
        // If there was found a page:
        if (is_array($row)) {
            // Creating elements
            $editUids = [];
            // Getting children
            $theRows = $this->getPageRecordsRecursive($row['uid'], $depth);
            if ($this->getBackendUser()->doesUserHaveAccess($row, Permission::PAGE_EDIT) && $row['uid'] > 0) {
                $editUids[] = $row['uid'];
            }
            $out .= $this->pages_drawItem($row, $this->fieldArray, $request);
            // Traverse all pages selected:
            foreach ($theRows as $sRow) {
                if ($this->getBackendUser()->doesUserHaveAccess($sRow, Permission::PAGE_EDIT)) {
                    $editUids[] = $sRow['uid'];
                }
                $out .= $this->pages_drawItem($sRow, $this->fieldArray, $request);
            }
            // Header line is drawn
            $headerCells = [];
            $editIdList = implode(',', $editUids);
            // Traverse fields (as set above) in order to create header values:
            foreach ($this->fieldArray as $field) {
                $editButton = '';
                if (
                    $editIdList
                    && isset($GLOBALS['TCA']['pages']['columns'][$field]) && $field !== 'uid'
                    && $this->getBackendUser()->check('tables_modify', 'pages')
                    && $this->getBackendUser()->check('non_exclude_fields', 'pages:' . $field)
                ) {
                    $iTitle = sprintf(
                        $lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:editThisColumn'),
                        rtrim(trim($lang->sL(BackendUtility::getItemLabel('pages', $field))), ':')
                    );
                    $urlParameters = [
                        'edit' => [
                            'pages' => [
                                $editIdList => 'edit',
                            ],
                        ],
                        'columnsOnly' => $field,
                        'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
                    ];
                    $url = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                    $editButton = '<a class="btn btn-default" href="' . htmlspecialchars($url)
                        . '" title="' . htmlspecialchars($iTitle) . '">'
                        . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render() . '</a>';
                }
                switch ($field) {
                    case 'title':
                        $headerCells[$field] = $editButton . '&nbsp;<strong>'
                            . $lang->sL($GLOBALS['TCA']['pages']['columns'][$field]['label'])
                            . '</strong>';
                        break;
                    case 'uid':
                        $headerCells[$field] = '';
                        break;
                    case 'actual_backend_layout':
                        $headerCells[$field] = htmlspecialchars($lang->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:actual_backend_layout'));
                        break;
                    default:
                        if (strpos($field, 'table_') === 0) {
                            $f2 = substr($field, 6);
                            if ($GLOBALS['TCA'][$f2]) {
                                $headerCells[$field] = '&nbsp;' .
                                    '<span title="' .
                                    htmlspecialchars($lang->sL($GLOBALS['TCA'][$f2]['ctrl']['title'])) .
                                    '">' .
                                    $this->iconFactory->getIconForRecord($f2, [], Icon::SIZE_SMALL)->render() .
                                    '</span>';
                            }
                        } else {
                            $headerCells[$field] = $editButton . '&nbsp;<strong>'
                                . htmlspecialchars($lang->sL($GLOBALS['TCA']['pages']['columns'][$field]['label']))
                                . '</strong>';
                        }
                }
            }
            $out = '<div class="table-responsive">'
                . '<table class="table table-striped table-hover mb-0">'
                . '<thead>'
                . $this->addElement($headerCells)
                . '</thead>'
                . '<tbody>'
                . $out
                . '</tbody>'
                . '</table>'
                . '</div>';
        }
        return $out;
    }

    /**
     * Adds pages-rows to an array, selecting recursively in the page tree.
     *
     * @param int $pid Starting page id to select from
     * @param string $iconPrefix Prefix for icon code.
     * @param int $depth Depth (decreasing)
     * @param array $rows Array which will accumulate page rows
     * @return array $rows with added rows.
     */
    protected function getPageRecordsRecursive(int $pid, int $depth, string $iconPrefix = '', array $rows = []): array
    {
        $depth--;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));

        $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW)
            );

        if (!empty($GLOBALS['TCA']['pages']['ctrl']['sortby'])) {
            $queryBuilder->orderBy($GLOBALS['TCA']['pages']['ctrl']['sortby']);
        }

        if ($depth >= 0) {
            $result = $queryBuilder->executeQuery();
            $rowCount = $queryBuilder->count('uid')->executeQuery()->fetchOne();
            $count = 0;
            while ($row = $result->fetchAssociative()) {
                BackendUtility::workspaceOL('pages', $row);
                if (is_array($row)) {
                    $count++;
                    $row['treeIcons'] = $iconPrefix
                        . '<span class="treeline-icon treeline-icon-join'
                        . ($rowCount === $count ? 'bottom' : '')
                        . '"></span>';
                    $rows[] = $row;
                    // Get the branch
                    $spaceOutIcons = '<span class="treeline-icon treeline-icon-'
                        . ($rowCount === $count ? 'clear' : 'line')
                        . '"></span>';
                    $rows = $this->getPageRecordsRecursive(
                        $row['uid'],
                        $row['php_tree_stop'] ? 0 : $depth,
                        $iconPrefix . $spaceOutIcons,
                        $rows
                    );
                }
            }
        }

        return $rows;
    }

    /**
     * Adds a list item for the pages-rendering
     *
     * @param array $row Record array
     * @param array $fieldArr Field list
     * @param ServerRequestInterface $request
     * @return string HTML for the item
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function pages_drawItem($row, $fieldArr, ServerRequestInterface $request)
    {
        $this->backendLayoutView = GeneralUtility::makeInstance(BackendLayoutView::class);
        $backendLayouts = $this->getBackendLayouts($row, 'backend_layout');
        $backendLayoutsNextLevel = $this->getBackendLayouts($row, 'backend_layout_next_level');
        $userTsConfig = $this->getBackendUser()->getTSConfig();
        $theIcon = $this->getIcon($row);
        // Preparing and getting the data-array
        $theData = [];
        foreach ($fieldArr as $field) {
            switch ($field) {
                case 'title':
                    $showPageId = !empty($userTsConfig['options.']['pageTree.']['showPageIdWithTitle']);
                    $pTitle = htmlspecialchars((string)BackendUtility::getProcessedValue('pages', $field, $row[$field], 20));
                    $theData[$field] = ($row['treeIcons'] ?? '') . $theIcon . ($showPageId ? '[' . $row['uid'] . '] ' : '') . $pTitle;
                    break;
                case 'php_tree_stop':
                    // Intended fall through
                case 'TSconfig':
                    $theData[$field] = $row[$field] ? '<strong>x</strong>' : '&nbsp;';
                    break;
                case 'actual_backend_layout':
                    $backendLayout = $this->backendLayoutView->getBackendLayoutForPage((int)$row['uid']);
                    $theData[$field] = $backendLayout !== null
                        ? htmlspecialchars($this->getLanguageService()->sL($backendLayout->getTitle()))
                        : '';
                    break;
                case 'backend_layout':
                    $layoutValue = $backendLayouts[$row[$field]] ?? null;
                    $theData[$field] = $this->resolveBackendLayoutValue($layoutValue, $field, $row);
                    break;
                case 'backend_layout_next_level':
                    $layoutValue = $backendLayoutsNextLevel[$row[$field]] ?? null;
                    $theData[$field] = $this->resolveBackendLayoutValue($layoutValue, $field, $row);
                    break;
                case 'uid':
                    $uid = 0;
                    $editButton = '';
                    if ($this->getBackendUser()->doesUserHaveAccess($row, 2) && $row['uid'] > 0) {
                        $uid = (int)$row['uid'];
                        $urlParameters = [
                            'edit' => [
                                'pages' => [
                                    $row['uid'] => 'edit',
                                ],
                            ],
                            'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
                        ];
                        $url = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                        $attributes = PreviewUriBuilder::create((int)$row['uid'])
                            ->withRootLine(BackendUtility::BEgetRootLine($row['uid']))
                            ->serializeDispatcherAttributes();
                        $editButton =
                            '<a href="#" ' . $attributes . ' class="btn btn-default" title="' .
                            htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage')) . '">' .
                            $this->iconFactory->getIcon('actions-view-page', Icon::SIZE_SMALL)->render() .
                            '</a>';

                        if ($this->getBackendUser()->check('tables_modify', 'pages')) {
                            $editButton .=
                                '<a class="btn btn-default" href="' . htmlspecialchars($url) . '" title="' .
                                htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:editDefaultLanguagePage')) . '">' .
                                $this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL)->render() .
                                '</a>';
                        }
                    }
                    // Since the uid is overwritten with the edit button markup we need to store
                    // the actual uid to be able to add it as data attribute to the table data cell.
                    // This also makes distinction between record rows and the header line simpler.
                    $theData['_UID_'] = $uid;
                    $theData[$field] = '<div class="btn-group" role="group">' . $editButton . '</div>';
                    break;
                case 'shortcut':
                case 'shortcut_mode':
                    if ((int)$row['doktype'] === PageRepository::DOKTYPE_SHORTCUT) {
                        $theData[$field] = $this->getPagesTableFieldValue($field, $row);
                    }
                    break;
                default:
                    if (strpos($field, 'table_') === 0) {
                        $f2 = substr($field, 6);
                        if ($GLOBALS['TCA'][$f2]) {
                            $c = $this->numberOfRecords($f2, $row['uid']);
                            $theData[$field] = ($c ?: '');
                        }
                    } else {
                        $theData[$field] = $this->getPagesTableFieldValue($field, $row);
                    }
            }
        }
        $this->addElement_tdCssClass['title'] = $row['_CSSCLASS'] ?? '';
        return $this->addElement($theData);
    }

    /**
     * Creates the icon image tag for the page and wraps it in a link which will trigger the click menu.
     *
     * @param array $row Record array
     * @return string HTML for the icon
     */
    protected function getIcon($row)
    {
        // Initialization
        $toolTip = BackendUtility::getRecordToolTip($row, 'pages');
        $icon = '<span ' . $toolTip . '>' . $this->iconFactory->getIconForRecord('pages', $row, Icon::SIZE_SMALL)->render() . '</span>';
        // The icon with link
        if ($this->getBackendUser()->recordEditAccessInternals('pages', $row)) {
            $icon = (string)BackendUtility::wrapClickMenuOnIcon($icon, 'pages', $row['uid']);
        }
        return $icon;
    }
    /**
     * Returns the HTML code for rendering a field in the pages table.
     * The row value is processed to a human readable form and the result is parsed through htmlspecialchars().
     *
     * @param string $field The name of the field of which the value should be rendered.
     * @param array $row The pages table row as an associative array.
     * @return string The rendered table field value.
     */
    protected function getPagesTableFieldValue($field, array $row)
    {
        return htmlspecialchars((string)BackendUtility::getProcessedValue('pages', $field, $row[$field]));
    }

    /**
     * Counts and returns the number of records on the page with $pid
     *
     * @param string $table Table name
     * @param int $pid Page id
     * @return int Number of records.
     */
    protected function numberOfRecords($table, $pid)
    {
        $count = 0;
        if ($GLOBALS['TCA'][$table]) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));
            $count = (int)$queryBuilder->count('uid')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT))
                )
                ->executeQuery()
                ->fetchOne();
        }

        return $count;
    }

    /**
     * Returns a table-row with the content from the fields in the input data array.
     * OBS: $this->fieldArray MUST be set! (represents the list of fields to display)
     *
     * @param array $data Is the data array, record with the fields. Notice: These fields are (currently) NOT htmlspecialchar'ed before being wrapped in <td>-tags
     * @return string HTML content for the table row
     */
    protected function addElement($data)
    {
        // Start up:
        $attributes = '';
        $rowTag = 'th';
        if (isset($data['_UID_'])) {
            $l10nParent = isset($data['_l10nparent_']) ? (int)$data['_l10nparent_'] : 0;
            $attributes = ' data-uid="' . $data['_UID_'] . '" data-l10nparent="' . $l10nParent . '"';
            $rowTag = 'td';
        }
        $out = '<tr' . $attributes . '>';
        // Init rendering.
        $colsp = '';
        $lastKey = '';
        $c = 0;
        // __label is used as the label key to circumvent problems with uid used as label (see #67756)
        // as it was introduced later on, check if it really exists before using it
        $fields = $this->fieldArray;
        if (array_key_exists('__label', $data)) {
            $fields[0] = '__label';
        }
        // Traverse field array which contains the data to present:
        foreach ($fields as $vKey) {
            if (isset($data[$vKey])) {
                if ($lastKey) {
                    $cssClass = $this->addElement_tdCssClass[$lastKey] ?? '';
                    $out .= '
						<' . $rowTag . ' class="' . $cssClass . ' nowrap"' . $colsp . '>' . $data[$lastKey] . '</' . $rowTag . '>';
                }
                $lastKey = $vKey;
                $c = 1;
            } else {
                if (!$lastKey) {
                    $lastKey = $vKey;
                }
                $c++;
            }
            if ($c > 1) {
                $colsp = ' colspan="' . $c . '"';
            } else {
                $colsp = '';
            }
        }
        if ($lastKey) {
            $cssClass = $this->addElement_tdCssClass[$lastKey] ?? '';
            $out .= '
				<' . $rowTag . ' class="' . $cssClass . ' nowrap"' . $colsp . '>' . $data[$lastKey] . '</' . $rowTag . '>';
        }
        $out .= '</tr>';
        return $out;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return LanguageService|null
     */
    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }

    protected function getBackendLayouts(array $row, string $field): array
    {
        if ($this->backendLayoutView === null) {
            $this->backendLayoutView = GeneralUtility::makeInstance(BackendLayoutView::class);
        }
        $configuration = ['row' => $row, 'table' => 'pages', 'field' => $field, 'items' => []];
        // Below we call the itemsProcFunc to retrieve properly resolved backend layout items,
        // including the translated labels and the correct field values (backend layout identifiers).
        $this->backendLayoutView->addBackendLayoutItems($configuration);
        $backendLayouts = [];
        foreach ($configuration['items'] ?? [] as $backendLayout) {
            if (($backendLayout[0] ?? false) && ($backendLayout[1] ?? false)) {
                $backendLayouts[$backendLayout[1]] = $backendLayout[0];
            }
        }
        return $backendLayouts;
    }

    protected function resolveBackendLayoutValue(?string $layoutValue, string $field, array $row): string
    {
        if ($layoutValue !== null) {
            // Directly return the resolved layout value from BackendLayoutView
            return htmlspecialchars($layoutValue);
        }

        // Fetch field value from database (this is already htmlspecialchared')
        $layoutValue = $this->getPagesTableFieldValue($field, $row);
        if ($layoutValue !== '') {
            // In case getPagesTableFieldValue returns a non-empty string, the database field
            // is filled with an invalid value (the backend layout does not longer exists).
            $layoutValue = sprintf(
                $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue'),
                $this->getPagesTableFieldValue($field, $row)
            );
        }

        return $layoutValue;
    }
}
