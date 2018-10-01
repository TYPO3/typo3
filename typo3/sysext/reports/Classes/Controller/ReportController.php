<?php
declare(strict_types = 1);
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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Compatibility\PublicMethodDeprecationTrait;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Reports\ReportInterface;
use TYPO3\CMS\Reports\RequestAwareReportInterface;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * Reports controller
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class ReportController
{
    use PublicMethodDeprecationTrait;

    /**
     * @var array
     */
    private $deprecatedPublicMethods = [
        'indexAction' => 'Using ReportController::indexAction() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'detailAction' => 'Using ReportController::detailAction() is deprecated and will not be possible anymore in TYPO3 v10.0.',
    ];

    /**
     * ModuleTemplate object
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * @var ViewInterface
     */
    protected $view;

    /**
     * Module name for the shortcut
     *
     * @var string
     */
    protected $shortcutName;

    /**
     * Instantiate the report controller
     */
    public function __construct()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
    }

    /**
     * Injects the request object for the current request, and renders correct action
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $action = $request->getQueryParams()['action'] ?? $request->getParsedBody()['action'] ?? '';
        $extension = $request->getQueryParams()['extension'] ?? $request->getParsedBody()['extension'];
        $isRedirect = $request->getQueryParams()['redirect'] ?? $request->getParsedBody()['redirect'] ?? false;

        if ($action !== 'index' && !$isRedirect && !$extension
            && is_array($GLOBALS['BE_USER']->uc['reports']['selection'])) {
            $previousSelection = $GLOBALS['BE_USER']->uc['reports']['selection'];
            if (!empty($previousSelection['extension']) && !empty($previousSelection['report'])) {
                $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                return new RedirectResponse((string)$uriBuilder->buildUriFromRoute('system_reports', [
                    'action' => 'detail',
                    'extension' => $previousSelection['extension'],
                    'report' => $previousSelection['report'],
                    'redirect' => 1,
                ]), 303);
            }
        }
        if (empty($action)) {
            $action = 'index';
        }

        $this->initializeView($action);

        if ($action === 'index') {
            $this->indexAction();
        } elseif ($action === 'detail') {
            $this->detailAction($request);
        } else {
            throw new \RuntimeException(
                'Reports module has only "index" and "detail" action, ' . (string)$action . ' given',
                1536322935
            );
        }

        $this->generateMenu($request);
        $this->generateButtons();

        $this->moduleTemplate->setContent($this->view->render());
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * @param string $templateName
     */
    protected function initializeView(string $templateName)
    {
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->setTemplate($templateName);
        $this->view->setTemplateRootPaths(['EXT:reports/Resources/Private/Templates/Report']);
        $this->view->setPartialRootPaths(['EXT:reports/Resources/Private/Partials']);
        $this->view->setLayoutRootPaths(['EXT:reports/Resources/Private/Layouts']);
        $this->view->getRequest()->setControllerExtensionName('Reports');
    }

    /**
     * Overview
     */
    protected function indexAction()
    {
        $this->view->assign('reports', $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']);
        $this->saveState();
    }

    /**
     * Display a single report
     *
     * @param ServerRequestInterface $request
     */
    protected function detailAction(ServerRequestInterface $request)
    {
        $content = $error = '';
        $extension = $request->getQueryParams()['extension'] ?? $request->getParsedBody()['extension'];
        $report = $request->getQueryParams()['report'] ?? $request->getParsedBody()['report'];

        $reportClass = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports'][$extension][$report]['report'] ?? null;

        $reportInstance = GeneralUtility::makeInstance($reportClass, $this);

        if ($reportInstance instanceof ReportInterface) {
            if ($reportInstance instanceof RequestAwareReportInterface) {
                $content = $reportInstance->getReport($request);
            } else {
                $content = $reportInstance->getReport();
            }
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
     *
     * @param ServerRequestInterface $request
     */
    protected function generateMenu(ServerRequestInterface $request)
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $lang = $this->getLanguageService();
        $lang->includeLLFile('EXT:reports/Resources/Private/Language/locallang.xlf');
        $menu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('WebFuncJumpMenu');
        $menuItem = $menu
            ->makeMenuItem()
            ->setHref(
                $uriBuilder->buildUriFromRoute('system_reports', ['action' => 'index'])
            )
            ->setTitle($lang->getLL('reports_overview'));
        $menu->addMenuItem($menuItem);
        $this->shortcutName = $lang->getLL('reports_overview');

        $extensionParam = $request->getQueryParams()['extension'] ?? $request->getParsedBody()['extension'];
        $reportParam = $request->getQueryParams()['report'] ?? $request->getParsedBody()['report'];

        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports'] as $extKey => $reports) {
            foreach ($reports as $reportName => $report) {
                $menuItem = $menu
                    ->makeMenuItem()
                    ->setHref($uriBuilder->buildUriFromRoute(
                        'system_reports',
                        ['action' => 'detail', 'extension' => $extKey, 'report' => $reportName]
                    ))
                    ->setTitle($this->getLanguageService()->sL($report['title']));
                if ($extensionParam === $extKey && $reportParam === $reportName) {
                    $menuItem->setActive(true);
                    $this->shortcutName = $menuItem->getTitle();
                }
                $menu->addMenuItem($menuItem);
            }
        }
        $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    /**
     * Gets all buttons for the docHeader
     */
    protected function generateButtons()
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setModuleName('system_reports')
            ->setGetVariables(['action', 'extension', 'report'])
            ->setDisplayName($this->shortcutName);
        $buttonBar->addButton($shortcutButton);
    }

    /**
     * Save the selected report
     *
     * @param string $extension Extension name
     * @param string $report Report name
     */
    protected function saveState(string $extension = '', string $report = '')
    {
        $this->getBackendUser()->uc['reports']['selection'] = [
            'extension' => $extension,
            'report' => $report,
        ];
        $this->getBackendUser()->writeUC();
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
