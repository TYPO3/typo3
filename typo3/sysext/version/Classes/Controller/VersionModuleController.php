<?php
namespace TYPO3\CMS\Version\Controller;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\DiffUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Versioning module, including workspace management
 */
class VersionModuleController extends \TYPO3\CMS\Backend\Module\BaseScriptClass
{
    /**
     * Module configuration
     *
     * @var array
     */
    public $MCONF = array();

    /**
     * Module menu items
     *
     * @var array
     */
    public $MOD_MENU = array();

    /**
     * Module session settings
     *
     * @var array
     */
    public $MOD_SETTINGS = array();

    /**
     * document template object
     *
     * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    public $doc;

    /**
     * @var string
     */
    public $content;

    /**
     * Accumulated content
     *
     * @var int
     */
    public $showWorkspaceCol = 0;

    /**
     * @var array
     */
    public $formatWorkspace_cache = array();

    /**
     * @var array
     */
    public $formatCount_cache = array();

    /**
     * @var array
     */
    public $targets = array();

    /**
     * Accumulation of online targets.
     *
     * @var string
     */
    public $pageModule = '';

    /**
     * Name of page module
     *
     * @var bool
     */
    public $publishAccess = false;

    /**
     * @var array
     */
    public $stageIndex = array();

    /**
     * @var array
     */
    public $recIndex = array();

    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'web_txversionM1';

    /**
     * ModuleTemplate object
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * Initialize language files
     */
    public function __construct()
    {
        $GLOBALS['SOBE'] = $this;
        $this->getLanguageService()->includeLLFile('EXT:version/Resources/Private/Language/locallang.xlf');
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
    }

    /**
     * Initialize menu configuration
     *
     * @return void
     */
    public function menuConfig()
    {
        // CLEANSE SETTINGS
        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, GeneralUtility::_GP('SET'), $this->moduleName, 'ses');
    }

    /**
     * Main function of the module. Write the content to $this->content
     *
     * @return void
     */
    public function main()
    {
        // Template markers
        $markers = array(
            'CSH' => '',
            'FUNC_MENU' => '',
            'WS_MENU' => '',
            'CONTENT' => ''
        );
        // Setting module configuration:
        $this->MCONF['name'] = $this->moduleName;
        $this->REQUEST_URI = str_replace('&sendToReview=1', '', GeneralUtility::getIndpEnv('REQUEST_URI'));
        // Draw the header.
        $this->doc = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\DocumentTemplate::class);
        $this->doc->setModuleTemplate('EXT:version/Resources/Private/Templates/version.html');

        // Setting up the context sensitive menu:
        $this->getPageRenderer()->loadJquery();
        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ClickMenu');
        // Getting input data:
        $this->id = (int)GeneralUtility::_GP('id');

        // Record uid. Goes with table name to indicate specific record
        $this->uid = (int)GeneralUtility::_GP('uid');
        // // Record table. Goes with uid to indicate specific record
        $this->table = GeneralUtility::_GP('table');

        $this->details = GeneralUtility::_GP('details');
        // Page id. If set, indicates activation from Web>Versioning module
        $this->diffOnly = GeneralUtility::_GP('diffOnly');
        // Flag. If set, shows only the offline version and with diff-view
        // Force this setting:
        $this->MOD_SETTINGS['expandSubElements'] = true;
        $this->MOD_SETTINGS['diff'] = $this->details || $this->MOD_SETTINGS['diff'] ? 1 : 0;
        // Reading the record:
        $record = BackendUtility::getRecord($this->table, $this->uid);
        if ($record['pid'] == -1) {
            $record = BackendUtility::getRecord($this->table, $record['t3ver_oid']);
        }
        $this->recordFound = is_array($record);
        $pidValue = $this->table === 'pages' ? $this->uid : $record['pid'];
        // Checking access etc.
        if ($this->recordFound && $GLOBALS['TCA'][$this->table]['ctrl']['versioningWS'] && !$this->id) {
            $this->uid = $record['uid'];
            // Might have changed if new live record was found!
            // Access check!
            // The page will show only if there is a valid page and if this page may be viewed by the user
            $this->pageinfo = BackendUtility::readPageAccess($pidValue, $this->perms_clause);
            $access = is_array($this->pageinfo) ? 1 : 0;
            if ($pidValue && $access || $GLOBALS['BE_USER']->user['admin'] && !$pidValue) {
                // If another page module was specified, replace the default Page module with the new one
                $newPageModule = trim($GLOBALS['BE_USER']->getTSConfigVal('options.overridePageModule'));
                $this->pageModule = BackendUtility::isModuleSetInTBE_MODULES($newPageModule) ? $newPageModule : 'web_layout';
                // Setting publish access permission for workspace:
                $this->publishAccess = $GLOBALS['BE_USER']->workspacePublishAccess($GLOBALS['BE_USER']->workspace);
                $this->versioningMgm();
            }
            // Setting up the buttons and markers for docheader
            $docHeaderButtons = $this->getButtons();
            $markers['CSH'] = $docHeaderButtons['csh'];
            $markers['FUNC_MENU'] = BackendUtility::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']);
            $markers['CONTENT'] = $this->content;
        } else {
            // If no access or id value, create empty document
            $this->content = '<h2>' . htmlspecialchars($this->getLanguageService()->getLL('clickAPage_header')) . '</h2><div>' . $this->getLanguageService()->getLL('clickAPage_content') . '</div>';
            // Setting up the buttons and markers for docheader
            $docHeaderButtons = $this->getButtons();
            $markers['CONTENT'] = $this->content;
        }
        // Build the <body> for the module
        $this->content = $this->doc->startPage($this->getLanguageService()->getLL('title'));
        $this->content .= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
        $this->content .= $this->doc->endPage();
        $this->content = $this->doc->insertStylesAndJS($this->content);
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     *
     * @return array All available buttons as an assoc. array
     */
    protected function getButtons()
    {
        $buttons = array(
            'csh' => '',
            'view' => '',
            'record_list' => '',
            'shortcut' => ''
        );
        // CSH
        if ($this->recordFound && $GLOBALS['TCA'][$this->table]['ctrl']['versioningWS']) {
            // View page
            $buttons['view'] = '
				<a href="#" onclick="' . htmlspecialchars(BackendUtility::viewOnClick($this->pageinfo['uid'], '', BackendUtility::BEgetRootLine($this->pageinfo['uid']))) . '" title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.showPage')) . '">
					' . $this->moduleTemplate->getIconFactory()->getIcon('actions-document-view', Icon::SIZE_SMALL)->render() . '
				</a>';
            // Shortcut
            if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
                $buttons['shortcut'] = $this->doc->makeShortcutIcon('id, edit_record, pointer, new_unique_uid, search_field, search_levels, showLimit', implode(',', array_keys($this->MOD_MENU)), $this->moduleName);
            }
            // If access to Web>List for user, then link to that module.
            $buttons['record_list'] = '<a href="' . htmlspecialchars(BackendUtility::getModuleUrl(
                'web_list',
                [
                    'id' => $this->pageinfo['uid'],
                    'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                ]
            )) . '">' . $this->moduleTemplate->getIconFactory()->getIcon('actions-system-list-open', Icon::SIZE_SMALL)->render() . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.showList')) . '</a>';
        }
        return $buttons;
    }

    /******************************
     *
     * Versioning management
     *
     ******************************/
    /**
     * Management of versions for record
     *
     * @return void
     */
    public function versioningMgm()
    {
        $lang = $this->getLanguageService();
        // Diffing:
        $diff_1 = GeneralUtility::_POST('diff_1');
        $diff_2 = GeneralUtility::_POST('diff_2');
        if (GeneralUtility::_POST('do_diff')) {
            $content = '';
            $content .= '<div class="panel panel-space panel-default">';
            $content .= '<div class="panel-heading">' . $lang->getLL('diffing') . '</div>';
            if ($diff_1 && $diff_2) {
                $diff_1_record = BackendUtility::getRecord($this->table, $diff_1);
                $diff_2_record = BackendUtility::getRecord($this->table, $diff_2);
                if (is_array($diff_1_record) && is_array($diff_2_record)) {
                    $diffUtility = GeneralUtility::makeInstance(DiffUtility::class);
                    $rows = array();
                    $rows[] = '
									<tr>
										<th>' . $lang->getLL('fieldname') . '</th>
										<th width="98%">' . $lang->getLL('coloredDiffView') . ':</th>
									</tr>
								';
                    foreach ($diff_1_record as $fN => $fV) {
                        if ($GLOBALS['TCA'][$this->table]['columns'][$fN] && $GLOBALS['TCA'][$this->table]['columns'][$fN]['config']['type'] !== 'passthrough' && $fN !== 't3ver_label') {
                            if ((string)$diff_1_record[$fN] !== (string)$diff_2_record[$fN]) {
                                $diffres = $diffUtility->makeDiffDisplay(
                                    BackendUtility::getProcessedValue($this->table, $fN, $diff_2_record[$fN], 0, 1),
                                    BackendUtility::getProcessedValue($this->table, $fN, $diff_1_record[$fN], 0, 1)
                                );
                                $rows[] = '
									<tr>
										<td>' . $fN . '</td>
										<td width="98%">' . $diffres . '</td>
									</tr>
								';
                            }
                        }
                    }
                    if (count($rows) > 1) {
                        $content .= '<div class="table-fit"><table class="table">' . implode('', $rows) . '</table></div>';
                    } else {
                        $content .= '<div class="panel-body">' . $lang->getLL('recordsMatchesCompletely') . '</div>';
                    }
                } else {
                    $content .= '<div class="panel-body">' . $lang->getLL('errorRecordsNotFound') . '</div>';
                }
            } else {
                $content .= '<div class="panel-body">' . $lang->getLL('errorDiffSources') . '</div>';
            }
            $content .= '</div>';
        }
        // Element:
        $record = BackendUtility::getRecord($this->table, $this->uid);
        $recTitle = BackendUtility::getRecordTitle($this->table, $record, true);
        // Display versions:
        $content .= '
			<form name="theform" action="' . str_replace('&sendToReview=1', '', $this->REQUEST_URI) . '" method="post">
				<div class="panel panel-space panel-default">
				<div class="panel-heading">' . $recTitle . '</div>
					<div class="table-fit">
						<table class="table">
							<thead>
								<tr>
									<th colspan="2" class="col-icon"></th>
									<th title="' . $lang->getLL('tblHeaderDesc_title') . '">' . $lang->getLL('tblHeader_title') . '</th>
									<th title="' . $lang->getLL('tblHeaderDesc_uid') . '"><i>' . $lang->getLL('tblHeader_uid') . '</i></th>
									<th title="' . $lang->getLL('tblHeaderDesc_t3ver_oid') . '"><i>' . $lang->getLL('tblHeader_t3ver_oid') . '</i></th>
									<th title="' . $lang->getLL('tblHeaderDesc_t3ver_id') . '"><i>' . $lang->getLL('tblHeader_t3ver_id') . '</i></th>
									<th title="' . $lang->getLL('tblHeaderDesc_t3ver_wsid') . '"><i>' . $lang->getLL('tblHeader_t3ver_wsid') . '</i></th>
									<th title="' . htmlspecialchars($lang->getLL('tblHeaderDesc_t3ver_state')) . '"><i>' . $lang->getLL('tblHeader_t3ver_state') . '</i></th>
									<th title="' . $lang->getLL('tblHeaderDesc_t3ver_stage') . '"><i>' . $lang->getLL('tblHeader_t3ver_stage') . '</i></th>
									<th title="' . $lang->getLL('tblHeaderDesc_t3ver_count') . '"><i>' . $lang->getLL('tblHeader_t3ver_count') . '</i></th>
									<th title="' . $lang->getLL('tblHeaderDesc_pid') . '"><i>' . $lang->getLL('tblHeader_pid') . '</i></th>
									<th title="' . $lang->getLL('tblHeaderDesc_t3ver_label') . '"><i>' . $lang->getLL('tblHeader_t3ver_label') . '</i></th>
									<th></th>
									<th colspan="2">
										<button class="btn btn-default btn-sm" type="submit"  name="do_diff" value="true">
											' . $lang->getLL('diff') . '
										</button>
									</th>
								</tr>
							</thead>
							<tbody>
			';
        $versions = BackendUtility::selectVersionsOfRecord($this->table, $this->uid, '*', $GLOBALS['BE_USER']->workspace);
        foreach ($versions as $row) {
            $adminLinks = $this->adminLinks($this->table, $row);

            $editUrl = BackendUtility::getModuleUrl('record_edit', [
                'edit' => [
                    $this->table => [
                        $row['uid'] => 'edit'
                    ]
                ],
                'columnsOnly' => 't3ver_label',
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
            ]);
            $content .= '
				<tr' . ($row['uid'] != $this->uid ? '' : ' class="active"') . '>
					<td class="col-icon">' .
                        ($row['uid'] != $this->uid ?
                            '<a href="' . BackendUtility::getLinkToDataHandlerAction('&cmd[' . $this->table . '][' . $this->uid . '][version][swapWith]=' . $row['uid'] . '&cmd[' . $this->table . '][' . $this->uid . '][version][action]=swap') . '" title="' . htmlspecialchars($lang->getLL('swapWithCurrent')) . '">' . $this->moduleTemplate->getIconFactory()->getIcon('actions-version-swap-version', Icon::SIZE_SMALL)->render() . '</a>' :
                            '<span title="' . htmlspecialchars($lang->getLL('currentOnlineVersion')) . '">' . $this->moduleTemplate->getIconFactory()->getIcon('status-status-current', Icon::SIZE_SMALL)->render() . '</span>'
                        ) . '
					</td>
					<td class="col-icon">' . $this->moduleTemplate->getIconFactory()->getIconForRecord($this->table, $row, Icon::SIZE_SMALL)->render() . '</td>
					<td>' . htmlspecialchars(BackendUtility::getRecordTitle($this->table, $row, true)) . '</td>
					<td>' . $row['uid'] . '</td>
					<td>' . $row['t3ver_oid'] . '</td>
					<td>' . $row['t3ver_id'] . '</td>
					<td>' . $row['t3ver_wsid'] . '</td>
					<td>' . $row['t3ver_state'] . '</td>
					<td>' . $row['t3ver_stage'] . '</td>
					<td>' . $row['t3ver_count'] . '</td>
					<td>' . $row['pid'] . '</td>
					<td>
						<a href="' . htmlspecialchars($editUrl) . '" title="' . htmlspecialchars($lang->sL('LLL:EXT:lang/locallang_core.xlf:cm.edit')) . '">
							' . $this->moduleTemplate->getIconFactory()->getIcon('actions-document-open', Icon::SIZE_SMALL)->render() . '
						</a>' . htmlspecialchars($row['t3ver_label']) . '
					</td>
					<td class="col-control">' . $adminLinks . '</td>
					<td class="text-center success"><input type="radio" name="diff_1" value="' . $row['uid'] . '"' . ($diff_1 == $row['uid'] ? ' checked="checked"' : '') . '/></td>
					<td class="text-center danger"><input type="radio" name="diff_2" value="' . $row['uid'] . '"' . ($diff_2 == $row['uid'] ? ' checked="checked"' : '') . '/></td>
				</tr>';
            // Show sub-content if the table is pages AND it is not the online branch (because that will mostly render the WHOLE tree below - not smart;)
            if ($this->table === 'pages' && $row['uid'] != $this->uid) {
                $sub = $this->pageSubContent($row['uid']);
                if ($sub) {
                    $content .= '
						<tr>
							<td colspan="2"></td>
							<td colspan="11">' . $sub . '</td>
							<td class="success"></td>
							<td class="danger"></td>
						</tr>';
                }
            }
        }
        $content .= '
							</tbody>
						</table>
					</div>
				</div>
			</form>';
        $this->content .= '<h2>' . htmlspecialchars($lang->getLL('title')) . '</h2><div>' . $content . '</div>';
        // Create new:
        $content = '
			<form action="' . htmlspecialchars(BackendUtility::getModuleUrl('tce_db')) . '" method="post">
				<div class="row">
					<div class="col-sm-6 col-md-4 col-lg-3">
						<div class="form-group">
							<label for="typo3-new-version-label">' . $lang->getLL('tblHeader_t3ver_label') . '</label>
							<input id="typo3-new-version-label" class="form-control" type="text" name="cmd[' . $this->table . '][' . $this->uid . '][version][label]" />
						</div>
						<div class="form-group">
							<input type="hidden" name="cmd[' . $this->table . '][' . $this->uid . '][version][action]" value="new" />
							<input type="hidden" name="prErr" value="1" />
							<input type="hidden" name="redirect" value="' . htmlspecialchars($this->REQUEST_URI) . '" />
							<input class="btn btn-default" type="submit" name="_" value="' . $lang->getLL('createNewVersion') . '" />
						</div>
					</div>
				</div>
			</form>

		';
        $this->content .= '<h2>' . htmlspecialchars($lang->getLL('createNewVersion')) . '</h2><div>' . $content . '</div>';
    }

    /**
     * Recursively look for children for page version with $pid
     *
     * @param int $pid UID of page record for which to look up sub-elements following that version
     * @param int $c Counter, do not set (limits to 100 levels)
     * @return string Table with content if any
     */
    public function pageSubContent($pid, $c = 0)
    {
        $tableNames = ArrayUtility::removeArrayEntryByValue(array_keys($GLOBALS['TCA']), 'pages');
        $tableNames[] = 'pages';
        $content = '';
        foreach ($tableNames as $table) {
            // Basically list ALL tables - not only those being copied might be found!
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

            $queryBuilder
                ->select('*')
                ->from($table)
                ->where($queryBuilder->expr()->eq('pid', (int)$pid));

            if (!empty($GLOBALS['TCA'][$table]['ctrl']['sortby'])) {
                $queryBuilder->orderBy($GLOBALS['TCA'][$table]['ctrl']['sortby']);
            }

            $result = $queryBuilder->execute();
            if ($result->rowCount()) {
                $content .= '
					<table class="table">
						<tr>
							<th class="col-icon">' . $this->moduleTemplate->getIconFactory()->getIconForRecord($table, array(), Icon::SIZE_SMALL)->render() . '</th>
							<th class="col-title">' . htmlspecialchars($this->getLanguageService()->sL($GLOBALS['TCA'][$table]['ctrl']['title'])) . '</th>
							<th></th>
							<th></th>
						</tr>';
                while ($subrow = $result->fetch()) {
                    $ownVer = $this->lookForOwnVersions($table, $subrow['uid']);
                    $content .= '
						<tr>
							<td class="col-icon">' . $this->moduleTemplate->getIconFactory()->getIconForRecord($table, $subrow, Icon::SIZE_SMALL)->render() . '</td>
							<td class="col-title">' . htmlspecialchars(BackendUtility::getRecordTitle($table, $subrow, true)) . '</td>
							<td>' . ($ownVer > 1 ? '<a href="' . htmlspecialchars(BackendUtility::getModuleUrl('web_txversionM1', array('table' => $table, 'uid' => $subrow['uid']))) . '">' . ($ownVer - 1) . '</a>' : '') . '</td>
							<td class="col-control">' . $this->adminLinks($table, $subrow) . '</td>
						</tr>';
                    if ($table == 'pages' && $c < 100) {
                        $sub = $this->pageSubContent($subrow['uid'], $c + 1);
                        if ($sub) {
                            $content .= '
								<tr>
									<td></td>
									<td></td>
									<td></td>
									<td width="98%">' . $sub . '</td>
								</tr>';
                        }
                    }
                }
                $content .= '</table>';
            }
        }
        return $content;
    }

    /**
     * Look for number of versions of a record
     *
     * @param string $table Table name
     * @param int $uid Record uid
     * @return int Number of versions for record, FALSE if none.
     */
    public function lookForOwnVersions($table, $uid)
    {
        $versions = BackendUtility::selectVersionsOfRecord($table, $uid, 'uid', null);
        if (is_array($versions)) {
            return count($versions);
        }
        return false;
    }

    /**
     * Administrative links for a table / record
     *
     * @param string $table Table name
     * @param array $row Record for which administrative links are generated.
     * @return string HTML link tags.
     */
    public function adminLinks($table, $row)
    {
        // Edit link:
        $editUrl = BackendUtility::getModuleUrl('record_edit', [
            'edit' => [
                $table => [
                    $row['uid'] => 'edit'
                ]
            ],
            'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
        ]);
        $adminLink = '<a class="btn btn-default" href="' . htmlspecialchars($editUrl) . '" title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:cm.edit')) . '">' . $this->moduleTemplate->getIconFactory()->getIcon('actions-document-open', Icon::SIZE_SMALL)->render() . '</a>';
        // Delete link:
        $adminLink .= '<a class="btn btn-default" href="' . htmlspecialchars(BackendUtility::getLinkToDataHandlerAction('&cmd[' . $table . '][' . $row['uid'] . '][delete]=1')) . '" title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:cm.delete')) . '">' . $this->moduleTemplate->getIconFactory()->getIcon('actions-edit-delete', Icon::SIZE_SMALL)->render() . '</a>';
        if ($table === 'pages') {
            // If another page module was specified, replace the default Page module with the new one
            $newPageModule = trim($GLOBALS['BE_USER']->getTSConfigVal('options.overridePageModule'));
            $pageModule = BackendUtility::isModuleSetInTBE_MODULES($newPageModule) ? $newPageModule : 'web_layout';
            // Perform some access checks:
            $a_wl = $GLOBALS['BE_USER']->check('modules', 'web_list');
            $a_wp = $GLOBALS['BE_USER']->check('modules', $pageModule);
            $adminLink .= '<a class="btn btn-default" href="#" onclick="top.loadEditId(' . $row['uid'] . ');top.goToModule(' . GeneralUtility::quoteJSvalue($pageModule) . '); return false;">'
                . $this->moduleTemplate->getIconFactory()->getIcon('actions-page-open', Icon::SIZE_SMALL)->render()
                . '</a>';
            $adminLink .= '<a class="btn btn-default" href="#" onclick="top.loadEditId(' . $row['uid'] . ');top.goToModule(\'web_list\'); return false;">' . $this->moduleTemplate->getIconFactory()->getIcon('actions-system-list-open', Icon::SIZE_SMALL)->render() . '</a>';
            // "View page" icon is added:
            $adminLink .= '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars(BackendUtility::viewOnClick($row['uid'], '', BackendUtility::BEgetRootLine($row['uid']))) . '">' . $this->moduleTemplate->getIconFactory()->getIcon('actions-document-view', Icon::SIZE_SMALL)->render() . '</a>';
        } else {
            if ($row['pid'] == -1) {
                $getVars = '&ADMCMD_vPrev[' . rawurlencode(($table . ':' . $row['t3ver_oid'])) . ']=' . $row['uid'];
                // "View page" icon is added:
                $adminLink .= '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars(BackendUtility::viewOnClick($row['_REAL_PID'], '', BackendUtility::BEgetRootLine($row['_REAL_PID']), '', '', $getVars)) . '">' . $this->moduleTemplate->getIconFactory()->getIcon('actions-document-view', Icon::SIZE_SMALL)->render() . '</a>';
            }
        }
        return '<div class="btn-group btn-group-sm" role="group">' . $adminLink . '</div>';
    }

    /**
     * Injects the request object for the current request and gathers all data.
     *
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response the prepared response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->init();
        $this->main();

        $response->getBody()->write($this->content);
        return $response;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
