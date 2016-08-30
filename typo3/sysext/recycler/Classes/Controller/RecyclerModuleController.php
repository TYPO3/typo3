<?php
namespace TYPO3\CMS\Recycler\Controller;

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
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

/**
 * Module 'Recycler' for the 'recycler' extension.
 */
class RecyclerModuleController extends ActionController
{
    /**
     * @var string
     */
    protected $relativePath;

    /**
     * @var string
     */
    public $perms_clause;

    /**
     * @var array
     */
    protected $pageRecord = [];

    /**
     * @var bool
     */
    protected $isAccessibleForCurrentUser = false;

    /**
     * @var bool
     */
    protected $allowDelete = false;

    /**
     * @var int
     */
    protected $recordsPageLimit = 50;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var BackendTemplateView
     */
    protected $view;

    /**
     * BackendTemplateView Container
     *
     * @var BackendTemplateView
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * Initializes the Module
     *
     * @return void
     */
    public function initializeAction()
    {
        $this->id = (int)GeneralUtility::_GP('id');
        $backendUser = $this->getBackendUser();
        $this->perms_clause = $backendUser->getPagePermsClause(1);
        $this->pageRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($this->id, $this->perms_clause);
        $this->isAccessibleForCurrentUser = $this->id && is_array($this->pageRecord) || !$this->id && $this->isCurrentUserAdmin();

        // don't access in workspace
        if ($backendUser->workspace !== 0) {
            $this->isAccessibleForCurrentUser = false;
        }

        // read configuration
        $modTS = $backendUser->getTSConfig('mod.recycler');
        if ($this->isCurrentUserAdmin()) {
            $this->allowDelete = true;
        } else {
            $this->allowDelete = (bool)$modTS['properties']['allowDelete'];
        }

        if (isset($modTS['properties']['recordsPageLimit']) && intval($modTS['properties']['recordsPageLimit']) > 0) {
            $this->recordsPageLimit = intval($modTS['properties']['recordsPageLimit']);
        }
    }

    /**
     * Initialize the view
     *
     * @param ViewInterface $view The view
     * @return void
     */
    public function initializeView(ViewInterface $view)
    {
        /** @var BackendTemplateView $view */
        parent::initializeView($view);
        $this->registerDocheaderButtons();
        $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());
    }

    /**
     * Renders the content of the module.
     *
     * @return void
     */
    public function indexAction()
    {
        // Integrate dynamic JavaScript such as configuration or lables:
        $jsConfiguration = $this->getJavaScriptConfiguration();
        $this->view->getModuleTemplate()->getPageRenderer()->addInlineSettingArray('Recycler', $jsConfiguration);
        $this->view->getModuleTemplate()->getPageRenderer()->addInlineLanguageLabelFile('EXT:recycler/Resources/Private/Language/locallang.xlf');
        if ($this->isAccessibleForCurrentUser) {
            $this->view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation($this->pageRecord);
        }

        $this->view->assign('title', $this->getLanguageService()->getLL('title'));
        $this->view->assign('allowDelete', $this->allowDelete);
    }

    /**
     * Registers the Icons into the docheader
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function registerDocheaderButtons()
    {
        /** @var ButtonBar $buttonBar */
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        $currentRequest = $this->request;
        $moduleName = $currentRequest->getPluginName();
        $getVars = $this->request->getArguments();

        $extensionName = $currentRequest->getControllerExtensionName();
        if (count($getVars) === 0) {
            $modulePrefix = strtolower('tx_' . $extensionName . '_' . $moduleName);
            $getVars = ['id', 'M', $modulePrefix];
        }
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setModuleName($moduleName)
            ->setGetVariables($getVars);
        $buttonBar->addButton($shortcutButton);

        $reloadButton = $buttonBar->makeLinkButton()
            ->setHref('#')
            ->setDataAttributes(['action' => 'reload'])
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:recycler/Resources/Private/Language/locallang.xlf:button.reload'))
            ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    /**
     * Determines whether the current user is admin.
     *
     * @return bool Whether the current user is admin
     */
    protected function isCurrentUserAdmin()
    {
        return (bool)$this->getBackendUser()->user['admin'];
    }

    /**
     * Gets the JavaScript configuration for the Ext JS interface.
     *
     * @return array The JavaScript configuration
     */
    protected function getJavaScriptConfiguration()
    {
        $configuration = [
            'pagingSize' => $this->recordsPageLimit,
            'showDepthMenu' => 1,
            'startUid' => (int)GeneralUtility::_GP('id'),
            'isSSL' => GeneralUtility::getIndpEnv('TYPO3_SSL'),
            'deleteDisable' => !$this->allowDelete,
            'depthSelection' => $this->getDataFromSession('depthSelection', 0),
            'tableSelection' => $this->getDataFromSession('tableSelection', ''),
            'States' => $this->getBackendUser()->uc['moduleData']['web_recycler']['States']
        ];
        return $configuration;
    }

    /**
     * Gets data from the session of the current backend user.
     *
     * @param string $identifier The identifier to be used to get the data
     * @param string $default The default date to be used if nothing was found in the session
     * @return string The accordant data in the session of the current backend user
     */
    protected function getDataFromSession($identifier, $default = null)
    {
        $sessionData = &$this->getBackendUser()->uc['tx_recycler'];
        if (isset($sessionData[$identifier]) && $sessionData[$identifier]) {
            $data = $sessionData[$identifier];
        } else {
            $data = $default;
        }
        return $data;
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
     * Returns an instance of DocumentTemplate
     *
     * @return \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    protected function getDocumentTemplate()
    {
        return $GLOBALS['TBE_TEMPLATE'];
    }

    /**
     * Returns an instance of LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
