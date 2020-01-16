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

use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Backend module page permissions
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class PermissionController extends ActionController
{
    /**
     * @var string prefix for session
     */
    const SESSION_PREFIX = 'tx_Beuser_';

    /**
     * @var int the current page id
     */
    protected $id;

    /**
     * @var string
     */
    protected $returnUrl = '';

    /**
     * @var int
     */
    protected $depth;

    /**
     * Number of levels to enable recursive settings for
     *
     * @var int
     */
    protected $getLevels = 10;

    /**
     * @var array
     */
    protected $pageInfo = [];

    /**
     * Backend Template Container
     *
     * @var string
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * BackendTemplateContainer
     *
     * @var BackendTemplateView
     */
    protected $view;

    /**
     * Initialize action
     */
    protected function initializeAction()
    {
        // determine depth parameter
        $this->depth = (int)GeneralUtility::_GP('depth') > 0
            ? (int)GeneralUtility::_GP('depth')
            : (int)$this->getBackendUser()->getSessionData(self::SESSION_PREFIX . 'depth');
        if ($this->request->hasArgument('depth')) {
            $this->depth = (int)$this->request->getArgument('depth');
        }
        $this->getBackendUser()->setAndSaveSessionData(self::SESSION_PREFIX . 'depth', $this->depth);

        // determine id parameter
        $this->id = (int)GeneralUtility::_GP('id');
        if ($this->request->hasArgument('id')) {
            $this->id = (int)$this->request->getArgument('id');
        }

        if (!BackendUtility::getRecord('pages', $this->id)) {
            $this->id = 0;
        }

        $this->returnUrl = GeneralUtility::_GP('returnUrl');
        if ($this->request->hasArgument('returnUrl')) {
            $this->returnUrl = $this->request->getArgument('returnUrl');
        }

        $this->setPageInfo();
    }

    /**
     * Initializes view
     *
     * @param ViewInterface $view The view to be initialized
     */
    protected function initializeView(ViewInterface $view)
    {
        parent::initializeView($view);
        $this->setPageInfo();
        $view->assign(
            'previewUrl',
            BackendUtility::viewOnClick(
                (int)$this->pageInfo['uid'],
                '',
                BackendUtility::BEgetRootLine((int)$this->pageInfo['uid'])
            )
        );

        // the view of the update action has a different view class
        if ($view instanceof BackendTemplateView) {
            $view->getModuleTemplate()->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Beuser/Permissions');
            $view->getModuleTemplate()->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/Tooltip');

            $this->registerDocHeaderButtons();
            $this->view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation($this->pageInfo);
            $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());
        }
    }

    /**
     * Registers the Icons into the docheader
     *
     * @throws \InvalidArgumentException
     */
    protected function registerDocHeaderButtons()
    {
        /** @var ButtonBar $buttonBar */
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        $currentRequest = $this->request;
        $moduleName = $currentRequest->getPluginName();
        $getVars = $this->request->getArguments();
        $lang = $this->getLanguageService();

        $extensionName = $currentRequest->getControllerExtensionName();
        if (empty($getVars)) {
            $modulePrefix = strtolower('tx_' . $extensionName . '_' . $moduleName);
            $getVars = ['id', 'route', $modulePrefix];
        }

        if ($currentRequest->getControllerActionName() === 'edit') {
            // CLOSE button:
            if (!empty($this->returnUrl)) {
                $closeButton = $buttonBar->makeLinkButton()
                    ->setHref($this->returnUrl)
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.closeDoc'))
                    ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon(
                        'actions-close',
                        Icon::SIZE_SMALL
                    ));
                $buttonBar->addButton($closeButton);
            }

            // SAVE button:
            $saveButton = $buttonBar->makeInputButton()
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.saveCloseDoc'))
                ->setName('tx_beuser_system_beusertxpermission[submit]')
                ->setValue('Save')
                ->setForm('PermissionControllerEdit')
                ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon(
                    'actions-document-save',
                    Icon::SIZE_SMALL
                ))
                ->setShowLabelText(true);

            $buttonBar->addButton($saveButton);
        }

        // SHORTCUT botton:
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setModuleName($moduleName)
            ->setGetVariables($getVars);
        $buttonBar->addButton($shortcutButton);
    }

    /**
     * Index action
     */
    public function indexAction()
    {
        if (!$this->id) {
            $this->pageInfo = ['title' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'], 'uid' => 0, 'pid' => 0];
        }

        if ($this->getBackendUser()->workspace != 0) {
            // Adding section with the permission setting matrix:
            $this->addFlashMessage(
                LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:WorkspaceWarningText', 'beuser'),
                LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:WorkspaceWarning', 'beuser'),
                FlashMessage::WARNING
            );
        }

        // depth options
        $depthOptions = [];
        $url = $this->uriBuilder->reset()->setArguments([
            'action' => 'index',
            'depth' => '__DEPTH__',
            'id' => $this->id
        ])->buildBackendUri();
        foreach ([1, 2, 3, 4, 10] as $depthLevel) {
            $levelLabel = $depthLevel === 1 ? 'level' : 'levels';
            $depthOptions[$depthLevel] = $depthLevel . ' ' . LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:' . $levelLabel, 'beuser');
        }
        $this->view->assign('currentId', $this->id);
        $this->view->assign('depthBaseUrl', $url);
        $this->view->assign('depth', $this->depth);
        $this->view->assign('depthOptions', $depthOptions);

        $beUserArray = BackendUtility::getUserNames();
        $this->view->assign('beUsers', $beUserArray);
        $beGroupArray = BackendUtility::getGroupNames();
        $this->view->assign('beGroups', $beGroupArray);

        /** @var PageTreeView $tree */
        $tree = GeneralUtility::makeInstance(PageTreeView::class);
        $tree->init();
        $tree->addField('perms_user', true);
        $tree->addField('perms_group', true);
        $tree->addField('perms_everybody', true);
        $tree->addField('perms_userid', true);
        $tree->addField('perms_groupid', true);
        $tree->addField('hidden');
        $tree->addField('fe_group');
        $tree->addField('starttime');
        $tree->addField('endtime');
        $tree->addField('editlock');

        // Create the tree from $this->id
        if ($this->id) {
            $tree->tree[] = ['row' => $this->pageInfo, 'HTML' => $tree->getIcon($this->id)];
        } else {
            $tree->tree[] = ['row' => $this->pageInfo, 'HTML' => $tree->getRootIcon($this->pageInfo)];
        }
        $tree->getTree($this->id, $this->depth);
        $this->view->assign('viewTree', $tree->tree);

        // CSH for permissions setting
        $this->view->assign('cshItem', BackendUtility::cshItem('xMOD_csh_corebe', 'perm_module', null, '<span class="btn btn-default btn-sm">|</span>'));
    }

    /**
     * Edit action
     */
    public function editAction()
    {
        $this->view->assign('id', $this->id);
        $this->view->assign('depth', $this->depth);

        if (!$this->id) {
            $this->pageInfo = ['title' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'], 'uid' => 0, 'pid' => 0];
        }
        if ($this->getBackendUser()->workspace != 0) {
            // Adding FlashMessage with the permission setting matrix:
            $this->addFlashMessage(
                LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:WorkspaceWarningText', 'beuser'),
                LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:WorkspaceWarning', 'beuser'),
                FlashMessage::WARNING
            );
        }
        // Get user names and group names
        $beGroupArray = BackendUtility::getGroupNames();
        $beUserArray  = BackendUtility::getUserNames();

        // Owner selector
        $beUserDataArray = [0 => LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:selectNone', 'beuser')];
        foreach ($beUserArray as $uid => &$row) {
            $beUserDataArray[$uid] = $row['username'];
        }
        $beUserDataArray[-1] = LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:selectUnchanged', 'beuser');
        $this->view->assign('currentBeUser', $this->pageInfo['perms_userid']);
        $this->view->assign('beUserData', $beUserDataArray);

        // Group selector
        $beGroupDataArray = [0 => LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:selectNone', 'beuser')];
        foreach ($beGroupArray as $uid => $row) {
            $beGroupDataArray[$uid] = $row['title'];
        }
        $beGroupDataArray[-1] = LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:selectUnchanged', 'beuser');
        $this->view->assign('currentBeGroup', $this->pageInfo['perms_groupid']);
        $this->view->assign('beGroupData', $beGroupDataArray);
        $this->view->assign('pageInfo', $this->pageInfo);
        $this->view->assign('returnUrl', $this->returnUrl);
        $this->view->assign('recursiveSelectOptions', $this->getRecursiveSelectOptions());
    }

    /**
     * Update action
     *
     * @param array $data
     * @param array $mirror
     */
    protected function updateAction(array $data, array $mirror)
    {
        $dataHandlerInput = [];
        // Prepare the input data for data handler
        if (!empty($data['pages'])) {
            foreach ($data['pages'] as $pageUid => $properties) {
                // if the owner and group field shouldn't be touched, unset the option
                if ((int)$properties['perms_userid'] === -1) {
                    unset($properties['perms_userid']);
                }
                if ((int)$properties['perms_groupid'] === -1) {
                    unset($properties['perms_groupid']);
                }
                $dataHandlerInput[$pageUid] = $properties;
                if (!empty($mirror['pages'][$pageUid])) {
                    $mirrorPages = GeneralUtility::intExplode(',', $mirror['pages'][$pageUid]);
                    foreach ($mirrorPages as $mirrorPageUid) {
                        $dataHandlerInput[$mirrorPageUid] = $properties;
                    }
                }
            }
        }

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'pages' => $dataHandlerInput
            ],
            []
        );
        $dataHandler->process_datamap();

        $this->redirectToUri($this->returnUrl);
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Finding tree and offer setting of values recursively.
     *
     * @return array
     */
    protected function getRecursiveSelectOptions()
    {
        // Initialize tree object:
        $tree = GeneralUtility::makeInstance(PageTreeView::class);
        $tree->init();
        $tree->addField('perms_userid', true);
        $tree->makeHTML = 0;
        $tree->setRecs = 1;
        // Make tree:
        $tree->getTree($this->id, $this->getLevels, '');
        $options = [];
        $options[''] = '';
        // If there are a hierarchy of page ids, then...
        if ($this->getBackendUser()->user['uid'] && !empty($tree->orig_ids_hierarchy)) {
            // Init:
            $labelRecursive = LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:recursive', 'beuser');
            $labelLevel = LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:level', 'beuser');
            $labelLevels = LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:levels', 'beuser');
            $labelPageAffected = LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:page_affected', 'beuser');
            $labelPagesAffected = LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:pages_affected', 'beuser');
            $theIdListArr = [];
            // Traverse the number of levels we want to allow recursive
            // setting of permissions for:
            for ($a = $this->getLevels; $a > 0; $a--) {
                if (is_array($tree->orig_ids_hierarchy[$a])) {
                    foreach ($tree->orig_ids_hierarchy[$a] as $theId) {
                        $theIdListArr[] = $theId;
                    }
                    $lKey = $this->getLevels - $a + 1;
                    $pagesCount = count($theIdListArr);
                    $options[implode(',', $theIdListArr)] = $labelRecursive . ' ' . $lKey . ' ' . ($lKey === 1 ? $labelLevel : $labelLevels) .
                        ' (' . $pagesCount . ' ' . ($pagesCount === 1 ? $labelPageAffected : $labelPagesAffected) . ')';
                }
            }
        }
        return $options;
    }

    /**
     * Check if page record exists and set pageInfo
     */
    protected function setPageInfo(): void
    {
        $this->pageInfo = BackendUtility::readPageAccess(BackendUtility::getRecord('pages', $this->id) ? $this->id : 0, ' 1=1');
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
