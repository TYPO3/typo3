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
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * This class extends the permissions module in the TYPO3 Backend to provide
 * convenient methods of editing of page permissions (including page ownership
 * (user and group)) via new AjaxRequestHandler facility
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class PermissionAjaxController
{
    /**
     * The local configuration array
     *
     * @var array
     */
    protected $conf;

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
        $this->getLanguageService()->includeLLFile('EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf');
    }

    /**
     * The main dispatcher function. Collect data and prepare HTML output.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $this->conf = [
            'page' => $parsedBody['page'] ?? null,
            'who' => $parsedBody['who'] ?? null,
            'mode' => $parsedBody['mode'] ?? null,
            'bits' => (int)($parsedBody['bits'] ?? 0),
            'permissions' => (int)($parsedBody['permissions'] ?? 0),
            'action' => $parsedBody['action'] ?? null,
            'ownerUid' => (int)($parsedBody['ownerUid'] ?? 0),
            'username' => $parsedBody['username'] ?? null,
            'groupUid' => (int)($parsedBody['groupUid'] ?? 0),
            'groupname' => $parsedBody['groupname'] ?? '',
            'editLockState' => (int)($parsedBody['editLockState'] ?? 0),
            'new_owner_uid' => (int)($parsedBody['newOwnerUid'] ?? 0),
            'new_group_uid' => (int)($parsedBody['newGroupUid'] ?? 0),
        ];

        $extPath = ExtensionManagementUtility::extPath('beuser');

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setPartialRootPaths(['default' => ExtensionManagementUtility::extPath('beuser') . 'Resources/Private/Partials']);
        $view->assign('pageId', $this->conf['page']);

        $response = new HtmlResponse('');

        // Basic test for required value
        if ($this->conf['page'] <= 0) {
            $response->getBody()->write('This script cannot be called directly');
            return $response->withStatus(500);
        }

        $content = '';
        // Init TCE for execution of update
        $tce = GeneralUtility::makeInstance(DataHandler::class);
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
        $response->getBody()->write($content);
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
        $saveButton = '<a class="saveowner btn btn-default" data-page="' . $page . '" data-owner="' . $ownerUid
                        . '" data-element-id="' . $elementId . '" title="Change owner">'
                        . $this->iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL)->render()
                        . '</a>';
        $cancelButton = '<a class="restoreowner btn btn-default" data-page="' . $page . '"  data-owner="' . $ownerUid
                        . '" data-element-id="' . $elementId . '"'
                        . (!empty($username) ? ' data-username="' . htmlspecialchars($username) . '"' : '')
                        . ' title="Cancel">'
                        . $this->iconFactory->getIcon('actions-close', Icon::SIZE_SMALL)->render()
                        . '</a>';
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

        // Get group names
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
        $saveButton = '<a class="savegroup btn btn-default" data-page="' . $page . '" data-group-id="' . $groupUid
                        . '" data-element-id="' . $elementId . '" title="Change group">'
                        . $this->iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL)->render()
                        . '</a>';
        $cancelButton = '<a class="restoregroup btn btn-default" data-page="' . $page . '" data-group-id="' . $groupUid
                        . '" data-element-id="' . $elementId . '"'
                        . (!empty($groupname) ? ' data-groupname="' . htmlspecialchars($groupname) . '"' : '')
                        . ' title="Cancel">'
                        . $this->iconFactory->getIcon('actions-close', Icon::SIZE_SMALL)->render()
                        . '</a>';
        return '<span id="' . $elementId . '">'
            . $selector
            . '<span class="btn-group">'
            . $saveButton
            . $cancelButton
            . '</span>'
            . '</span>';
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
            $ret = '<span id="el_' . $page . '"><a class="editlock btn btn-default" data-page="' . $page
                    . '" data-lockstate="1" title="The page and all content is locked for editing by all non-Admin users.">'
                    . $this->iconFactory->getIcon('actions-lock', Icon::SIZE_SMALL)->render() . '</a></span>';
        } else {
            $ret = '<span id="el_' . $page . '"><a class="editlock btn btn-default" data-page="' . $page .
                    '" data-lockstate="0" title="Enable the &raquo;Admin-only&laquo; edit lock for this page">'
                    . $this->iconFactory->getIcon('actions-unlock', Icon::SIZE_SMALL)->render() . '</a></span>';
        }
        return $ret;
    }

    /**
     * @return \TYPO3\CMS\Core\Localization\LanguageService
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
