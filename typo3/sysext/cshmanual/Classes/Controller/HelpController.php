<?php
namespace TYPO3\CMS\Cshmanual\Controller;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Cshmanual\Domain\Repository\TableManualRepository;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Main help module controller
 */
class HelpController extends ActionController
{
    /**
     * Section identifiers
     */
    const FULL = 0;

    /**
     * Show only Table of contents
     */
    const TOC_ONLY = 1;

    /**
     * @var TableManualRepository
     */
    protected $tableManualRepository;

    /**
     * Default View Container
     *
     * @var BackendTemplateView
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * Initialize the controller
     *
     * @return void
     */
    public function initializeAction()
    {
        $this->tableManualRepository = GeneralUtility::makeInstance(TableManualRepository::class);
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
        $view->assign('copyright', BackendUtility::TYPO3_copyRightNotice());
    }

    /**
     * Show table of contents
     *
     * @return void
     */
    public function indexAction()
    {
        $this->view->assign('toc', $this->tableManualRepository->getSections(self::TOC_ONLY));
    }

    /**
     * Show the table of contents and all manuals
     *
     * @return void
     */
    public function allAction()
    {
        $this->view->assign('all', $this->tableManualRepository->getSections(self::FULL));
    }

    /**
     * Show a single manual
     *
     * @param string $table
     * @param string $field
     * @return void
     */
    public function detailAction($table = '', $field = '*')
    {
        if (empty($table)) {
            $this->forward('index');
        }

        $mainKey = $table;
        $identifierParts = GeneralUtility::trimExplode('.', $field);
        // The field is the second one
        if (count($identifierParts) > 1) {
            array_shift($field);
            // There's at least one extra part
            $extraIdentifierInformation = [];
            $extraIdentifierInformation[] = array_shift($identifierParts);
            // If the ds_pointerField contains a comma, it means the choice of FlexForm DS
            // is determined by 2 parameters. In this case we have an extra identifier part
            if (strpos($GLOBALS['TCA'][$table]['columns'][$field]['config']['ds_pointerField'], ',') !== false) {
                $extraIdentifierInformation[] = array_shift($identifierParts);
            }
            // The remaining parts make up the FlexForm field name itself (reassembled with dots)
            $flexFormField = implode('.', $identifierParts);
            // Assemble a different main key and switch field to use FlexForm field name
            $mainKey .= '.' . $field;
            foreach ($extraIdentifierInformation as $extraKey) {
                $mainKey .= '.' . $extraKey;
            }
            $field = $flexFormField;
        }

        $this->view->assignMultiple([
            'table' => $table,
            'key' => $mainKey,
            'field' => $field,
            'manuals' => $field === '*' ? $this->tableManualRepository->getTableManual($mainKey) : [$this->tableManualRepository->getSingleManual($mainKey, $field)],
        ]);
    }

    /**
     * Registers the Icons into the docheader
     *
     * @throws \InvalidArgumentException
     */
    protected function registerDocheaderButtons()
    {
        /** @var ButtonBar $buttonBar */
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        $currentRequest = $this->request;
        $moduleName = $currentRequest->getPluginName();
        $getVars = $this->request->getArguments();

        $mayMakeShortcut = $this->getBackendUser()->mayMakeShortcut();

        if ($mayMakeShortcut) {
            $extensionName = $currentRequest->getControllerExtensionName();
            if (count($getVars) === 0) {
                $modulePrefix = strtolower('tx_' . $extensionName . '_' . $moduleName);
                $getVars = ['id', 'M', $modulePrefix];
            }
            $shortcutButton = $buttonBar->makeShortcutButton()
                ->setModuleName($moduleName)
                ->setGetVariables($getVars);
            $buttonBar->addButton($shortcutButton);
        }
        if (isset($getVars['action']) && $getVars['action'] !== 'index') {
            $backButton = $buttonBar->makeLinkButton()
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/locallang_common.xlf:back'))
                ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-view-go-up', Icon::SIZE_SMALL))
                ->setHref(BackendUtility::getModuleUrl($moduleName));
            $buttonBar->addButton($backButton);
        }
    }

    /**
     * Returns the currently logged in BE user
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
