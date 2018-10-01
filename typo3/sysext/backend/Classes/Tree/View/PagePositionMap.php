<?php
namespace TYPO3\CMS\Backend\Tree\View;

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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Position map class - generating a page tree / content element list which links for inserting (copy/move) of records.
 * Used for pages / tt_content element wizards of various kinds.
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class PagePositionMap
{
    use PublicPropertyDeprecationTrait;

    /**
     * Properties which have been moved to protected status from public
     *
     * @var array
     */
    protected $deprecatedPublicProperties = [
        'getModConfigCache' => 'Using $getModConfigCache of class PagePositionMap is discouraged. This property will be removed in TYPO3 v10.0.',
        'modConfigStr' => 'Using $$modConfigStr of class PagePositionMap is discouraged. This property will be removed in TYPO3 v10.0.',
    ];

    // EXTERNAL, static:
    /**
     * @var string
     */
    public $moveOrCopy = 'move';

    /**
     * @var int
     */
    public $dontPrintPageInsertIcons = 0;

    // How deep the position page tree will go.
    /**
     * @var int
     */
    public $depth = 2;

    // Can be set to the sys_language uid to select content elements for.
    /**
     * @var string
     */
    public $cur_sys_language;

    // INTERNAL, dynamic:
    // Request uri
    /**
     * @var string
     */
    public $R_URI = '';

    // Element id.
    /**
     * @var string
     */
    public $elUid = '';

    // tt_content element uid to move.
    /**
     * @var string
     */
    public $moveUid = '';

    // Caching arrays:
    /**
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    protected $getModConfigCache = [];

    /**
     * @var array
     */
    public $checkNewPageCache = [];

    // Label keys:
    /**
     * @var string
     */
    public $l_insertNewPageHere = 'insertNewPageHere';

    /**
     * @var string
     */
    public $l_insertNewRecordHere = 'insertNewRecordHere';

    /**
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    protected $modConfigStr = 'mod.web_list.newPageWiz';

    /**
     * Page tree implementation class name
     *
     * @var string
     */
    protected $pageTreeClassName = ElementBrowserPageTreeView::class;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var string
     */
    protected $clientContext;

    /**
     * Constructor allowing to set pageTreeImplementation
     *
     * @param string $pageTreeClassName
     * @param string $clientContext JavaScript context of view client (either 'window' or 'list_frame')
     */
    public function __construct(string $pageTreeClassName = null, string $clientContext = 'window')
    {
        if ($pageTreeClassName !== null) {
            $this->pageTreeClassName = $pageTreeClassName;
        }
        $this->clientContext = $clientContext;
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /*************************************
     *
     * Page position map:
     *
     **************************************/
    /**
     * Creates a "position tree" based on the page tree.
     *
     * @param int $id Current page id
     * @param array $pageinfo Current page record.
     * @param string $perms_clause Page selection permission clause.
     * @param string $R_URI Current REQUEST_URI
     * @return string HTML code for the tree.
     */
    public function positionTree($id, $pageinfo, $perms_clause, $R_URI)
    {
        // Make page tree object:
        /** @var \TYPO3\CMS\Backend\Tree\View\PageTreeView $pageTree */
        $pageTree = GeneralUtility::makeInstance($this->pageTreeClassName);
        $pageTree->init(' AND ' . $perms_clause);
        $pageTree->addField('pid');
        // Initialize variables:
        $this->R_URI = $R_URI;
        $this->elUid = $id;
        // Create page tree, in $this->depth levels.
        $pageTree->getTree($pageinfo['pid'], $this->depth);
        // Initialize variables:
        $saveLatestUid = [];
        $latestInvDepth = $this->depth;
        // Traverse the tree:
        $lines = [];
        foreach ($pageTree->tree as $cc => $dat) {
            if ($latestInvDepth > $dat['invertedDepth']) {
                $margin = 'style="margin-left: ' . ($dat['invertedDepth'] * 16 + 9) . 'px;"';
                $lines[] = '<ul class="list-tree" ' . $margin . '>';
            }
            // Make link + parameters.
            $latestInvDepth = $dat['invertedDepth'];
            $saveLatestUid[$latestInvDepth] = $dat;
            if (isset($pageTree->tree[$cc - 1])) {
                $prev_dat = $pageTree->tree[$cc - 1];
                // If current page, subpage?
                if ($prev_dat['row']['uid'] == $id) {
                    // 1) It must be allowed to create a new page and 2) If there are subpages there is no need to render a subpage icon here - it'll be done over the subpages...
                    if (!$this->dontPrintPageInsertIcons && $this->checkNewPageInPid($id) && !($prev_dat['invertedDepth'] > $pageTree->tree[$cc]['invertedDepth'])) {
                        end($lines);
                        $margin = 'style="margin-left: ' . (($dat['invertedDepth'] - 1) * 16 + 9) . 'px;"';
                        $lines[] = '<ul class="list-tree" ' . $margin . '><li><span class="text-nowrap"><a href="#" onclick="' . htmlspecialchars($this->onClickEvent($id, $id)) . '"><i class="t3-icon fa fa-long-arrow-left" title="' . $this->insertlabel() . '"></i></a></span></li></ul>';
                    }
                }
                // If going down
                if ($prev_dat['invertedDepth'] > $pageTree->tree[$cc]['invertedDepth']) {
                    $prevPid = $pageTree->tree[$cc]['row']['pid'];
                } elseif ($prev_dat['invertedDepth'] < $pageTree->tree[$cc]['invertedDepth']) {
                    // If going up
                    // First of all the previous level should have an icon:
                    if (!$this->dontPrintPageInsertIcons && $this->checkNewPageInPid($prev_dat['row']['pid'])) {
                        $prevPid = -$prev_dat['row']['uid'];
                        end($lines);
                        $lines[] = '<li><span class="text-nowrap"><a href="#" onclick="' . htmlspecialchars($this->onClickEvent($prevPid, $prev_dat['row']['pid'])) . '"><i class="t3-icon fa fa-long-arrow-left" title="' . $this->insertlabel() . '"></i></a></span></li>';
                    }
                    // Then set the current prevPid
                    $prevPid = -$prev_dat['row']['pid'];
                    if ($prevPid !== $dat['row']['pid']) {
                        $lines[] = '</ul>';
                    }
                } else {
                    // In on the same level
                    $prevPid = -$prev_dat['row']['uid'];
                }
            } else {
                // First in the tree
                $prevPid = $dat['row']['pid'];
            }
            // print arrow on the same level
            if (!$this->dontPrintPageInsertIcons && $this->checkNewPageInPid($dat['row']['pid'])) {
                $lines[] = '<span class="text-nowrap"><a href="#" onclick="' . htmlspecialchars($this->onClickEvent($prevPid, $dat['row']['pid'])) . '"><i class="t3-icon fa fa-long-arrow-left" title="' . $this->insertlabel() . '"></i></a></span>';
            }
            // The line with the icon and title:
            $toolTip = BackendUtility::getRecordToolTip($dat['row'], 'pages');
            $icon = '<span ' . $toolTip . '>' . $this->iconFactory->getIconForRecord('pages', $dat['row'], Icon::SIZE_SMALL)->render() . '</span>';

            $lines[] = '<span class="text-nowrap">' . $icon . $this->linkPageTitle($this->boldTitle(htmlspecialchars(GeneralUtility::fixed_lgd_cs($dat['row']['title'], $this->getBackendUser()->uc['titleLen'])), $dat, $id), $dat['row']) . '</span>';
        }
        // If the current page was the last in the tree:
        $prev_dat = end($pageTree->tree);
        if ($prev_dat['row']['uid'] == $id) {
            if (!$this->dontPrintPageInsertIcons && $this->checkNewPageInPid($id)) {
                $lines[] = '<ul class="list-tree" style="margin-left: 25px"><li><span class="text-nowrap"><a href="#" onclick="' . htmlspecialchars($this->onClickEvent($id, $id)) . '"><i class="t3-icon fa fa-long-arrow-left" title="' . $this->insertlabel() . '"></i></a></span></li></ul>';
            }
        }
        for ($a = $latestInvDepth; $a <= $this->depth; $a++) {
            $dat = $saveLatestUid[$a];
            $prevPid = -$dat['row']['uid'];
            if (!$this->dontPrintPageInsertIcons && $this->checkNewPageInPid($dat['row']['pid'])) {
                if ($latestInvDepth < $dat['invertedDepth']) {
                    $lines[] = '</ul>';
                }
                $lines[] = '<span class="text-nowrap"><a href="#" onclick="' . htmlspecialchars($this->onClickEvent($prevPid, $dat['row']['pid'])) . '"><i class="t3-icon fa fa-long-arrow-left" title="' . $this->insertlabel() . '"></i></a></span>';
            }
        }

        $code = '<ul class="list-tree">';

        foreach ($lines as $line) {
            if ((strpos($line, '<ul') === 0) || (strpos($line, '</ul') === 0)) {
                $code .= $line;
            } else {
                $code .= '<li>' . $line . '</li>';
            }
        }

        $code .= '</ul>';
        return $code;
    }

    /**
     * Wrap $t_code in bold IF the $dat uid matches $id
     *
     * @param string $t_code Title string
     * @param array $dat Information array with record array inside.
     * @param int $id The current id.
     * @return string The title string.
     */
    public function boldTitle($t_code, $dat, $id)
    {
        if ($dat['row']['uid'] == $id) {
            $t_code = '<strong>' . $t_code . '</strong>';
        }
        return $t_code;
    }

    /**
     * Creates the onclick event for the insert-icons.
     *
     * TSconfig mod.newPageWizard.override may contain an alternative module / route which can be
     * used instead of the normal create new page wizard.
     *
     * @param int $pid The pid.
     * @param int $newPagePID New page id.
     * @return string Onclick attribute content
     */
    public function onClickEvent($pid, $newPagePID)
    {
        $TSconfig = BackendUtility::getPagesTSconfig($newPagePID)['mod.']['newPageWizard.'] ?? [];
        if (isset($TSconfig['override']) && !empty($TSconfig['override'])) {
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $url = $uriBuilder->buildUriFromRoute(
                $TSconfig['override'],
                [
                    'positionPid' => $pid,
                    'newPageId'   => $newPagePID,
                    'cmd'         => 'crPage',
                    'returnUrl'   => GeneralUtility::getIndpEnv('REQUEST_URI')
                ]
            );
            return $this->clientContext . '.location.href=' . GeneralUtility::quoteJSvalue((string)$url) . ';';
        }
        $params = '&edit[pages][' . $pid . ']=new&returnNewPageId=1';
        return BackendUtility::editOnClick($params, '', $this->R_URI);
    }

    /**
     * Get label, htmlspecialchars()'ed
     *
     * @return string The localized label for "insert new page here
     */
    public function insertlabel()
    {
        return htmlspecialchars($this->getLanguageService()->getLL($this->l_insertNewPageHere));
    }

    /**
     * Wrapping page title.
     *
     * @param string $str Page title.
     * @param array $rec Page record (?)
     * @return string Wrapped title.
     */
    public function linkPageTitle($str, $rec)
    {
        return $str;
    }

    /**
     * Checks if the user has permission to created pages inside of the $pid page.
     * Uses caching so only one regular lookup is made - hence you can call the function multiple times without worrying about performance.
     *
     * @param int $pid Page id for which to test.
     * @return bool
     */
    public function checkNewPageInPid($pid)
    {
        if (!isset($this->checkNewPageCache[$pid])) {
            $pidInfo = BackendUtility::getRecord('pages', $pid);
            $this->checkNewPageCache[$pid] = $this->getBackendUser()->isAdmin() || $this->getBackendUser()->doesUserHaveAccess($pidInfo, 8);
        }
        return $this->checkNewPageCache[$pid];
    }

    /**
     * Returns module configuration for a pid.
     *
     * @param int $pid Page id for which to get the module configuration.
     * @return array The properties of the module configuration for the page id.
     * @see onClickEvent()
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function getModConfig($pid)
    {
        trigger_error('PagePositionMap->getModConfig() will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        if (!isset($this->getModConfigCache[$pid])) {
            // Acquiring TSconfig for this PID:
            $this->getModConfigCache[$pid]['properties'] = BackendUtility::getPagesTSconfig($pid)['mod.']['web_list.']['newPageWiz.'] ?? [];
        }
        return $this->getModConfigCache[$pid]['properties'];
    }

    /*************************************
     *
     * Content element positioning:
     *
     **************************************/
    /**
     * Creates HTML for inserting/moving content elements.
     *
     * @param int $pid page id onto which to insert content element.
     * @param int $moveUid Move-uid (tt_content element uid?)
     * @param string $colPosList List of columns to show
     * @param bool $showHidden If not set, then hidden/starttime/endtime records are filtered out.
     * @param string $R_URI Request URI
     * @return string HTML
     */
    public function printContentElementColumns($pid, $moveUid, $colPosList, $showHidden, $R_URI)
    {
        $this->R_URI = $R_URI;
        $this->moveUid = $moveUid;
        $colPosArray = GeneralUtility::trimExplode(',', $colPosList, true);
        $lines = [];
        foreach ($colPosArray as $kk => $vv) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
            if ($showHidden) {
                $queryBuilder->getRestrictions()
                    ->removeByType(HiddenRestriction::class)
                    ->removeByType(StartTimeRestriction::class)
                    ->removeByType(EndTimeRestriction::class);
            }
            $queryBuilder
                ->select('*')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq('colPos', $queryBuilder->createNamedParameter($vv, \PDO::PARAM_INT))
                )
                ->orderBy('sorting');

            if ((string)$this->cur_sys_language !== '') {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->eq(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter($this->cur_sys_language, \PDO::PARAM_INT)
                    )
                );
            }

            $res = $queryBuilder->execute();
            $lines[$vv] = [];
            $lines[$vv][] = $this->insertPositionIcon('', $vv, $kk, $moveUid, $pid);

            while ($row = $res->fetch()) {
                BackendUtility::workspaceOL('tt_content', $row);
                if (is_array($row)) {
                    $lines[$vv][] = $this->wrapRecordHeader($this->getRecordHeader($row), $row);
                    $lines[$vv][] = $this->insertPositionIcon($row, $vv, $kk, $moveUid, $pid);
                }
            }
        }
        return $this->printRecordMap($lines, $colPosArray, $pid);
    }

    /**
     * Creates the table with the content columns
     *
     * @param array $lines Array with arrays of lines for each column
     * @param array $colPosArray Column position array
     * @param int $pid The id of the page
     * @return string HTML
     */
    public function printRecordMap($lines, $colPosArray, $pid = 0)
    {
        $count = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange(count($colPosArray), 1);
        $backendLayout = GeneralUtility::callUserFunction(\TYPO3\CMS\Backend\View\BackendLayoutView::class . '->getSelectedBackendLayout', $pid, $this);
        if (isset($backendLayout['__config']['backend_layout.'])) {
            $this->getLanguageService()->includeLLFile('EXT:backend/Resources/Private/Language/locallang_layout.xlf');
            $table = '<div class="table-fit"><table class="table table-condensed table-bordered table-vertical-top">';
            $colCount = (int)$backendLayout['__config']['backend_layout.']['colCount'];
            $rowCount = (int)$backendLayout['__config']['backend_layout.']['rowCount'];
            $table .= '<colgroup>';
            for ($i = 0; $i < $colCount; $i++) {
                $table .= '<col style="width:' . 100 / $colCount . '%"></col>';
            }
            $table .= '</colgroup>';
            $table .= '<tbody>';
            $tcaItems = GeneralUtility::callUserFunction(\TYPO3\CMS\Backend\View\BackendLayoutView::class . '->getColPosListItemsParsed', $pid, $this);
            // Cycle through rows
            for ($row = 1; $row <= $rowCount; $row++) {
                $rowConfig = $backendLayout['__config']['backend_layout.']['rows.'][$row . '.'];
                if (!isset($rowConfig)) {
                    continue;
                }
                $table .= '<tr>';
                for ($col = 1; $col <= $colCount; $col++) {
                    $columnConfig = $rowConfig['columns.'][$col . '.'];
                    if (!isset($columnConfig)) {
                        continue;
                    }
                    // Which tt_content colPos should be displayed inside this cell
                    $columnKey = (int)$columnConfig['colPos'];
                    $head = '';
                    foreach ($tcaItems as $item) {
                        if ($item[1] == $columnKey) {
                            $head = htmlspecialchars($this->getLanguageService()->sL($item[0]));
                        }
                    }
                    // Render the grid cell
                    $table .= '<td'
                        . (isset($columnConfig['colspan']) ? ' colspan="' . $columnConfig['colspan'] . '"' : '')
                        . (isset($columnConfig['rowspan']) ? ' rowspan="' . $columnConfig['rowspan'] . '"' : '')
                        . ' class="col-nowrap col-min'
                        . (!isset($columnConfig['colPos']) ? ' warning' : '')
                        . (isset($columnConfig['colPos']) && !$head ? ' danger' : '') . '">';
                    // Render header
                    $table .= '<p>';
                    if (isset($columnConfig['colPos']) && $head) {
                        $table .= '<strong>' . $this->wrapColumnHeader($head, '') . '</strong>';
                    } elseif ($columnConfig['colPos']) {
                        $table .= '<em>' . $this->wrapColumnHeader($this->getLanguageService()->getLL('noAccess'), '') . '</em>';
                    } else {
                        $table .= '<em>' . $this->wrapColumnHeader(($this->getLanguageService()->sL($columnConfig['name']) ?: '') . ' (' . $this->getLanguageService()->getLL('notAssigned') . ')', '') . '</em>';
                    }
                    $table .= '</p>';
                    // Render lines
                    if (isset($columnConfig['colPos']) && $head && !empty($lines[$columnKey])) {
                        $table .= '<ul class="list-unstyled">';
                        foreach ($lines[$columnKey] as $line) {
                            $table .= '<li>' . $line . '</li>';
                        }
                        $table .= '</ul>';
                    }
                    $table .= '</td>';
                }
                $table .= '</tr>';
            }
            $table .= '</tbody>';
            $table .= '</table></div>';
        } else {
            // Traverse the columns here:
            $row = '';
            foreach ($colPosArray as $kk => $vv) {
                $row .= '<td class="col-nowrap col-min" width="' . round(100 / $count) . '%">';
                $row .= '<p><strong>' . $this->wrapColumnHeader(htmlspecialchars($this->getLanguageService()->sL(BackendUtility::getLabelFromItemlist('tt_content', 'colPos', $vv))), $vv) . '</strong></p>';
                if (!empty($lines[$vv])) {
                    $row .= '<ul class="list-unstyled">';
                    foreach ($lines[$vv] as $line) {
                        $row .= '<li>' . $line . '</li>';
                    }
                    $row .= '</ul>';
                }
                $row .= '</td>';
            }
            $table = '

			<!--
				Map of records in columns:
			-->
			<div class="table-fit">
				<table class="table table-condensed table-bordered table-vertical-top">
					<tr>' . $row . '</tr>
				</table>
			</div>

			';
        }
        return $table;
    }

    /**
     * Wrapping the column header
     *
     * @param string $str Header value
     * @param string $vv Column info.
     * @return string
     * @see printRecordMap()
     */
    public function wrapColumnHeader($str, $vv)
    {
        return $str;
    }

    /**
     * Creates a linked position icon.
     *
     * @param mixed $row Element row. If this is an array the link will cause an insert after this content element, otherwise
     * the link will insert at the first position in the column
     * @param string $vv Column position value.
     * @param int $kk Column key.
     * @param int $moveUid Move uid
     * @param int $pid PID value.
     * @return string
     */
    public function insertPositionIcon($row, $vv, $kk, $moveUid, $pid)
    {
        if (is_array($row) && !empty($row['uid'])) {
            // Use record uid for the hash when inserting after this content element
            $uid = $row['uid'];
        } else {
            // No uid means insert at first position in the column
            $uid = '';
        }
        $cc = hexdec(substr(md5($uid . '-' . $vv . '-' . $kk), 0, 4));
        return '<a href="#" onclick="' . htmlspecialchars($this->onClickInsertRecord($row, $vv, $moveUid, $pid, $this->cur_sys_language)) . '" data-dismiss="modal">' . '<i class="t3-icon fa fa-long-arrow-left" name="mImgEnd' . $cc . '" title="' . htmlspecialchars($this->getLanguageService()->getLL($this->l_insertNewRecordHere)) . '"></i></a>';
    }

    /**
     * Create on-click event value.
     *
     * @param mixed $row The record. If this is not an array with the record data the insert will be for the first position
     * in the column
     * @param string $vv Column position value.
     * @param int $moveUid Move uid
     * @param int $pid PID value.
     * @param int $sys_lang System language (not used currently)
     * @return string
     */
    public function onClickInsertRecord($row, $vv, $moveUid, $pid, $sys_lang = 0)
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        if (is_array($row)) {
            $location = $uriBuilder->buildUriFromRoute('tce_db', [
                'cmd[tt_content][' . $moveUid . '][' . $this->moveOrCopy . ']' => '-' . $row['uid'],
                'redirect' => $this->R_URI,
            ]);
        } else {
            $location = $uriBuilder->buildUriFromRoute('tce_db', [
                'cmd[tt_content][' . $moveUid . '][' . $this->moveOrCopy . ']' => $pid,
                'data[tt_content][' . $moveUid . '][colPos]' => $vv,
                'redirect' => $this->R_URI,
            ]);
        }
        // returns to prev. page
        return $this->clientContext . '.location.href=' . GeneralUtility::quoteJSvalue((string)$location) . ';return false;';
    }

    /**
     * Wrapping the record header  (from getRecordHeader())
     *
     * @param string $str HTML content
     * @param string $row Record array.
     * @return string HTML content
     */
    public function wrapRecordHeader($str, $row)
    {
        return $str;
    }

    /**
     * Create record header (includes the record icon, record title etc.)
     *
     * @param array $row Record row.
     * @return string HTML
     */
    public function getRecordHeader($row)
    {
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $toolTip = BackendUtility::getRecordToolTip($row, 'tt_content');
        $line = '<span ' . $toolTip . ' title="' . BackendUtility::getRecordIconAltText($row, 'tt_content') . '">' . $iconFactory->getIconForRecord('tt_content', $row, Icon::SIZE_SMALL)->render() . '</span>';
        $line .= BackendUtility::getRecordTitle('tt_content', $row, true);
        return $this->wrapRecordTitle($line, $row);
    }

    /**
     * Wrapping the title of the record.
     *
     * @param string $str The title value.
     * @param array $row The record row.
     * @return string Wrapped title string.
     */
    public function wrapRecordTitle($str, $row)
    {
        return '<a href="' . htmlspecialchars(GeneralUtility::linkThisScript(['uid' => (int)$row['uid'], 'moveUid' => ''])) . '">' . $str . '</a>';
    }

    /**
     * Returns the BackendUser
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns the LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
