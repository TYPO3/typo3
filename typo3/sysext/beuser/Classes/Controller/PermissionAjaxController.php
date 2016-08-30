<?php
namespace TYPO3\CMS\Beuser\Controller;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * This class extends the permissions module in the TYPO3 Backend to provide
 * convenient methods of editing of page permissions (including page ownership
 * (user and group)) via new AjaxRequestHandler facility
 */
class PermissionAjaxController
{
    /**
     * The local configuration array
     *
     * @var array
     */
    protected $conf = [];

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * The constructor of this class
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->getLanguageService()->includeLLFile('EXT:lang/locallang_mod_web_perm.xlf');
        // Configuration, variable assignment
        $this->conf['page'] = GeneralUtility::_POST('page');
        $this->conf['who'] = GeneralUtility::_POST('who');
        $this->conf['mode'] = GeneralUtility::_POST('mode');
        $this->conf['bits'] = (int)GeneralUtility::_POST('bits');
        $this->conf['permissions'] = (int)GeneralUtility::_POST('permissions');
        $this->conf['action'] = GeneralUtility::_POST('action');
        $this->conf['ownerUid'] = (int)GeneralUtility::_POST('ownerUid');
        $this->conf['username'] = GeneralUtility::_POST('username');
        $this->conf['groupUid'] = (int)GeneralUtility::_POST('groupUid');
        $this->conf['groupname'] = GeneralUtility::_POST('groupname');
        $this->conf['editLockState'] = (int)GeneralUtility::_POST('editLockState');
        $this->conf['new_owner_uid'] = (int)GeneralUtility::_POST('newOwnerUid');
        $this->conf['new_group_uid'] = (int)GeneralUtility::_POST('newGroupUid');
    }

    /**
     * The main dispatcher function. Collect data and prepare HTML output.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request, ResponseInterface $response)
    {
        $extPath = ExtensionManagementUtility::extPath('beuser');

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setPartialRootPaths(['default' => ExtensionManagementUtility::extPath('beuser') . 'Resources/Private/Partials']);
        $view->assign('pageId', $this->conf['page']);

        $content = '';
        // Basic test for required value
        if ($this->conf['page'] > 0) {
            // Init TCE for execution of update
            /** @var $tce DataHandler */
            $tce = GeneralUtility::makeInstance(DataHandler::class);
            $tce->stripslashes_values = false;
            // Determine the scripts to execute
            switch ($this->conf['action']) {
                case 'show_change_owner_selector':
                    $content = $this->renderUserSelector($this->conf['page'], $this->conf['ownerUid'], $this->conf['username']);
                    break;
                case 'change_owner':
                    $userId = $this->conf['new_owner_uid'];
                    if (is_int($userId)) {
                        // Prepare data to change
                        $data = [];
                        $data['pages'][$this->conf['page']]['perms_userid'] = $userId;
                        // Execute TCE Update
                        $tce->start($data, []);
                        $tce->process_datamap();

                        $view->setTemplatePathAndFilename($extPath . 'Resources/Private/Templates/PermissionAjax/ChangeOwner.html');
                        $view->assign('userId', $userId);
                        $usernameArray = BackendUtility::getUserNames('username', ' AND uid = ' . $userId);
                        $view->assign('username', $usernameArray[$userId]['username']);
                        $content = $view->render();
                    } else {
                        $response->getBody()->write('An error occurred: No page owner uid specified');
                        $response = $response->withStatus(500);
                    }
                    break;
                case 'show_change_group_selector':
                    $content = $this->renderGroupSelector($this->conf['page'], $this->conf['groupUid'], $this->conf['groupname']);
                    break;
                case 'change_group':
                    $groupId = $this->conf['new_group_uid'];
                    if (is_int($groupId)) {
                        // Prepare data to change
                        $data = [];
                        $data['pages'][$this->conf['page']]['perms_groupid'] = $groupId;
                        // Execute TCE Update
                        $tce->start($data, []);
                        $tce->process_datamap();

                        $view->setTemplatePathAndFilename($extPath . 'Resources/Private/Templates/PermissionAjax/ChangeGroup.html');
                        $view->assign('groupId', $groupId);
                        $groupnameArray = BackendUtility::getGroupNames('title', ' AND uid = ' . $groupId);
                        $view->assign('groupname', $groupnameArray[$groupId]['title']);
                        $content = $view->render();
                    } else {
                        $response->getBody()->write('An error occurred: No page group uid specified');
                        $response = $response->withStatus(500);
                    }
                    break;
                case 'toggle_edit_lock':
                    // Prepare data to change
                    $data = [];
                    $data['pages'][$this->conf['page']]['editlock'] = $this->conf['editLockState'] === 1 ? 0 : 1;
                    // Execute TCE Update
                    $tce->start($data, []);
                    $tce->process_datamap();
                    $content = $this->renderToggleEditLock($this->conf['page'], $data['pages'][$this->conf['page']]['editlock']);
                    break;
                default:
                    if ($this->conf['mode'] === 'delete') {
                        $this->conf['permissions'] = (int)($this->conf['permissions'] - $this->conf['bits']);
                    } else {
                        $this->conf['permissions'] = (int)($this->conf['permissions'] + $this->conf['bits']);
                    }
                    // Prepare data to change
                    $data = [];
                    $data['pages'][$this->conf['page']]['perms_' . $this->conf['who']] = $this->conf['permissions'];
                    // Execute TCE Update
                    $tce->start($data, []);
                    $tce->process_datamap();

                    $view->setTemplatePathAndFilename($extPath . 'Resources/Private/Templates/PermissionAjax/ChangePermission.html');
                    $view->assign('permission', $this->conf['permissions']);
                    $view->assign('scope', $this->conf['who']);
                    $content = $view->render();
            }
        } else {
            $response->getBody()->write('This script cannot be called directly');
            $response = $response->withStatus(500);
        }
        $response->getBody()->write($content);
        $response = $response->withHeader('Content-Type', 'text/html; charset=utf-8');
        return $response;
    }

    /**
     * Generate the user selector element
     *
     * @param int $page The page id to change the user for
     * @param int $ownerUid The page owner uid
     * @param string $username The username to display
     * @return string The html select element
     */
    protected function renderUserSelector($page, $ownerUid, $username = '')
    {
        $page = (int)$page;
        $ownerUid = (int)$ownerUid;
        // Get usernames
        $beUsers = BackendUtility::getUserNames();
        // Owner selector:
        $options = '';
        // Loop through the users
        foreach ($beUsers as $uid => $row) {
            $uid = (int)$uid;
            $selected = $uid === $ownerUid ? ' selected="selected"' : '';
            $options .= '<option value="' . $uid . '"' . $selected . '>' . htmlspecialchars($row['username']) . '</option>';
        }
        $elementId = 'o_' . $page;
        $options = '<option value="0"></option>' . $options;
        $selector = '<select name="new_page_owner" id="new_page_owner">' . $options . '</select>';
        $saveButton = '<a class="saveowner btn btn-default" data-page="' . $page . '" data-owner="' . $ownerUid . '" data-element-id="' . $elementId . '" title="Change owner">' . $this->iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL)->render() . '</a>';
        $cancelButton = '<a class="restoreowner btn btn-default" data-page="' . $page . '"  data-owner="' . $ownerUid . '" data-element-id="' . $elementId . '"' . (!empty($username) ? ' data-username="' . htmlspecialchars($username) . '"' : '') . ' title="Cancel">' . $this->iconFactory->getIcon('actions-document-close', Icon::SIZE_SMALL)->render() . '</a>';
        return '<span id="' . $elementId . '">'
            . $selector
            . '<span class="btn-group">'
            . $saveButton
            . $cancelButton
            . '</span>'
            . '</span>';
    }

    /**
     * Generate the group selector element
     *
     * @param int $page The page id to change the user for
     * @param int $groupUid The page group uid
     * @param string $groupname The groupname to display
     * @return string The html select element
     */
    protected function renderGroupSelector($page, $groupUid, $groupname = '')
    {
        $page = (int)$page;
        $groupUid = (int)$groupUid;

        // Get usernames
        $beGroupsO = $beGroups = BackendUtility::getGroupNames();
        // Group selector:
        $options = '';
        // flag: is set if the page-groupid equals one from the group-list
        $userset = 0;
        // Loop through the groups
        foreach ($beGroups as $uid => $row) {
            $uid = (int)$uid;
            if ($uid === $groupUid) {
                $userset = 1;
                $selected = ' selected="selected"';
            } else {
                $selected = '';
            }
            $options .= '<option value="' . $uid . '"' . $selected . '>' . htmlspecialchars($row['title']) . '</option>';
        }
        // If the group was not set AND there is a group for the page
        if (!$userset && $groupUid) {
            $options = '<option value="' . $groupUid . '" selected="selected">' .
                htmlspecialchars($beGroupsO[$groupUid]['title']) . '</option>' . $options;
        }
        $elementId = 'g_' . $page;
        $options = '<option value="0"></option>' . $options;
        $selector = '<select name="new_page_group" id="new_page_group">' . $options . '</select>';
        $saveButton = '<a class="savegroup btn btn-default" data-page="' . $page . '" data-group="' . $groupUid . '" data-element-id="' . $elementId . '" title="Change group">' . $this->iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL)->render() . '</a>';
        $cancelButton = '<a class="restoregroup btn btn-default" data-page="' . $page . '" data-group="' . $groupUid . '" data-element-id="' . $elementId . '"' . (!empty($groupname) ? ' data-groupname="' . htmlspecialchars($groupname) . '"' : '') . ' title="Cancel">' . $this->iconFactory->getIcon('actions-document-close', Icon::SIZE_SMALL)->render() . '</a>';
        return '<span id="' . $elementId . '">'
            . $selector
            . '<span class="btn-group">'
            . $saveButton
            . $cancelButton
            . '</span>'
            . '</span>';
    }

    /**
     * Print the string with the new owner of a page record
     *
     * @param int $page The TYPO3 page id
     * @param int $ownerUid The new page user uid
     * @param string $username The TYPO3 BE username (used to display in the element)
     * @param bool $validUser Must be set to FALSE, if the user has no name or is deleted
     * @return string The new group wrapped in HTML
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8. This is now solved with fluid.
     */
    public static function renderOwnername($page, $ownerUid, $username, $validUser = true)
    {
        GeneralUtility::logDeprecatedFunction();
        $elementId = 'o_' . $page;
        return '<span id="' . $elementId . '"><a class="ug_selector changeowner" data-page="' . $page . '" data-owner="' . $ownerUid . '" data-username="' . htmlspecialchars($username) . '">' . ($validUser ? ($username == '' ? '<span class=not_set>[' . $GLOBALS['LANG']->getLL('notSet') . ']</span>' : htmlspecialchars(GeneralUtility::fixed_lgd_cs($username, 20))) : '<span class=not_set title="' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($username, 20)) . '">[' . $GLOBALS['LANG']->getLL('deleted') . ']</span>') . '</a></span>';
    }

    /**
     * Print the string with the new group of a page record
     *
     * @param int $page The TYPO3 page id
     * @param int $groupUid The new page group uid
     * @param string $groupname The TYPO3 BE groupname (used to display in the element)
     * @param bool $validGroup Must be set to FALSE, if the group has no name or is deleted
     * @return string The new group wrapped in HTML
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8. This is now solved with fluid.
     */
    public static function renderGroupname($page, $groupUid, $groupname, $validGroup = true)
    {
        GeneralUtility::logDeprecatedFunction();
        $elementId = 'g_' . $page;
        return '<span id="' . $elementId . '"><a class="ug_selector changegroup" data-page="' . $page . '" data-group="' . $groupUid . '" data-groupname="' . htmlspecialchars($groupname) . '">' . ($validGroup ? ($groupname == '' ? '<span class=not_set>[' . $GLOBALS['LANG']->getLL('notSet') . ']</span>' : htmlspecialchars(GeneralUtility::fixed_lgd_cs($groupname, 20))) : '<span class=not_set title="' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($groupname, 20)) . '">[' . $GLOBALS['LANG']->getLL('deleted') . ']</span>') . '</a></span>';
    }

    /**
     * Print the string with the new edit lock state of a page record
     *
     * @param int $page The TYPO3 page id
     * @param string $editLockState The state of the TYPO3 page (locked, unlocked)
     * @return string The new edit lock string wrapped in HTML
     */
    protected function renderToggleEditLock($page, $editLockState)
    {
        $page = (int)$page;
        if ($editLockState === 1) {
            $ret = '<span id="el_' . $page . '"><a class="editlock btn btn-default" data-page="' . $page . '" data-lockstate="1" title="The page and all content is locked for editing by all non-Admin users.">' . $this->iconFactory->getIcon('actions-lock', Icon::SIZE_SMALL)->render() . '</a></span>';
        } else {
            $ret = '<span id="el_' . $page . '"><a class="editlock btn btn-default" data-page="' . $page . '" data-lockstate="0" title="Enable the &raquo;Admin-only&laquo; edit lock for this page">' . $this->iconFactory->getIcon('actions-unlock', Icon::SIZE_SMALL)->render() . '</a></span>';
        }
        return $ret;
    }

    /**
     * Print a set of permissions. Also used in index.php
     *
     * @param int $int Permission integer (bits)
     * @param int $pageId The TYPO3 page id
     * @param string $who The scope (user, group or everybody)
     * @return string HTML marked up x/* indications.
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8. This is now solved with fluid.
     */
    public static function renderPermissions($int, $pageId = 0, $who = 'user')
    {
        GeneralUtility::logDeprecatedFunction();
        $str = '';
        $permissions = [1, 16, 2, 4, 8];
        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        foreach ($permissions as $permission) {
            if ($int & $permission) {
                $str .= '<span title="' . $GLOBALS['LANG']->getLL($permission, true)
                    . ' class="change-permission text-success"'
                    . ' data-page="' . (int)$pageId . '"'
                    . ' data-permissions="' . (int)$int . '"'
                    . ' data-mode="delete"'
                    . ' data-who="' . htmlspecialchars($who) . '"'
                    . ' data-bits="' . $permission . '"'
                    . ' style="cursor:pointer">'
                    . $iconFactory->getIcon('status-status-permission-granted', Icon::SIZE_SMALL)->render()
                    . '</span>';
            } else {
                $str .= '<span title="' . $GLOBALS['LANG']->getLL($permission, true) . '"'
                    . ' class="change-permission text-danger"'
                    . ' data-page="' . (int)$pageId . '"'
                    . ' data-permissions="' . (int)$int . '"'
                    . ' data-mode="add"'
                    . ' data-who="' . htmlspecialchars($who) . '"'
                    . ' data-bits="' . $permission . '"'
                    . ' style="cursor:pointer">'
                    . $iconFactory->getIcon('status-status-permission-denied', Icon::SIZE_SMALL)->render()
                    . '</span>';
            }
        }
        return '<span id="' . $pageId . '_' . $who . '">' . $str . '</span>';
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
