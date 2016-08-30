<?php
namespace TYPO3\CMS\Reports\Controller;

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
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Reports\ReportInterface;

/**
 * Reports controller
 */
class ReportController extends ActionController
{
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
     * Module name for the shortcut
     *
     * @var string
     */
    protected $shortcutName;

    /**
     * Redirect to the saved report
     *
     * @return void
     */
    public function initializeAction()
    {
        $vars = GeneralUtility::_GET('tx_reports_system_reportstxreportsm1');
        if (!isset($vars['redirect']) && $vars['action'] !== 'index' && !isset($vars['extension']) && is_array($GLOBALS['BE_USER']->uc['reports']['selection'])) {
            $previousSelection = $GLOBALS['BE_USER']->uc['reports']['selection'];
            if (!empty($previousSelection['extension']) && !empty($previousSelection['report'])) {
                $this->redirect('detail', 'Report', null, [
                    'extension' => $previousSelection['extension'],
                    'report' => $previousSelection['report'],
                    'redirect' => 1,
                ]);
            } else {
                $this->redirect('index');
            }
        }
    }

    /**
     * Initialize the view
     *
     * @param ViewInterface $view The view
     *
     * @return void
     */
    protected function initializeView(ViewInterface $view)
    {
        /** @var BackendTemplateView $view */
        parent::initializeView($view);
        $view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation([]);
        $this->generateMenu();
        $this->generateButtons();
    }

    /**
     * Overview
     *
     * @return void
     */
    public function indexAction()
    {
        $this->view->assign(
            'reports', $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']
        );
        $this->saveState();
    }

    /**
     * Display a single report
     *
     * @param string $extension Extension
     * @param string $report Report
     *
     * @return void
     */
    public function detailAction($extension, $report)
    {
        $content = ($error = '');
        $reportClass = null;
        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports'][$extension])
            && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports'][$extension])
            && isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports'][$extension][$report])
            && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports'][$extension][$report])
            && isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports'][$extension][$report]['report'])
        ) {
            $reportClass = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports'][$extension][$report]['report'];
        }

        // If extension has been uninstalled/removed redirect to index
        if ($reportClass === null) {
            $this->redirect('index');
        }

        $reportInstance = GeneralUtility::makeInstance($reportClass, $this);
        if ($reportInstance instanceof ReportInterface) {
            $content = $reportInstance->getReport();
            $this->saveState($extension, $report);
        } else {
            $error = $reportClass . ' does not implement the Report Interface which is necessary to be displayed here.';
        }
        $this->view->assignMultiple([
            'content' => $content,
            'error' => $error,
            'report' => $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports'][$extension][$report],
        ]);
    }

    /**
     * Generates the menu
     */
    protected function generateMenu()
    {
        $lang = $this->getLanguageService();
        $lang->includeLLFile('EXT:reports/Resources/Private/Language/locallang.xlf');
        $menu = $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('WebFuncJumpMenu');
        $menuItem = $menu
            ->makeMenuItem()
            ->setHref(
                $this->uriBuilder->reset()->uriFor('index', null, 'Report')
            )
            ->setTitle($lang->getLL('reports_overview'));
        $menu->addMenuItem($menuItem);
        $this->shortcutName = $lang->getLL('reports_overview');
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports'] as $extKey => $reports) {
            foreach ($reports as $reportName => $report) {
                $menuItem = $menu
                    ->makeMenuItem()
                    ->setHref($this->uriBuilder->reset()->uriFor('detail',
                        ['extension' => $extKey, 'report' => $reportName], 'Report'))
                    ->setTitle($this->getLanguageService()->sL($report['title']));
                if ($this->arguments->hasArgument('extension') && $this->arguments->hasArgument('report')) {
                    if ($this->arguments->getArgument('extension')->getValue() === $extKey && $this->arguments->getArgument('report')->getValue() === $reportName) {
                        $menuItem->setActive(true);
                        $this->shortcutName = $menuItem->getTitle();
                    }
                }
                $menu->addMenuItem($menuItem);
            }
        }
        $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    /**
     * Gets all buttons for the docheader
     */
    protected function generateButtons()
    {
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        $moduleName = $this->request->getPluginName();
        $getVars = $this->request->hasArgument('getVars') ? $this->request->getArgument('getVars') : [];
        $setVars = $this->request->hasArgument('setVars') ? $this->request->getArgument('setVars') : [];
        if (count($getVars) === 0) {
            $modulePrefix = strtolower('tx_' . $this->request->getControllerExtensionName() . '_' . $moduleName);
            $getVars = ['id', 'M', $modulePrefix];
        }
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setModuleName($moduleName)
            ->setGetVariables($getVars)
            ->setDisplayName($this->shortcutName)
            ->setSetVariables($setVars);
        $buttonBar->addButton($shortcutButton);
    }

    /**
     * Save the selected report
     *
     * @param string $extension Extension name
     * @param string $report Report name
     *
     * @return void
     */
    protected function saveState($extension = '', $report = '')
    {
        $GLOBALS['BE_USER']->uc['reports']['selection'] = [
            'extension' => $extension,
            'report' => $report,
        ];
        $GLOBALS['BE_USER']->writeUC();
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
