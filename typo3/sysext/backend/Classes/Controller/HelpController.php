<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Controller;

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
use TYPO3\CMS\Backend\Domain\Repository\TableManualRepository;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * Main "CSH help" module controller
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class HelpController
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

    /** @var ModuleTemplate */
    protected $moduleTemplate;

    /** @var ViewInterface */
    protected $view;

    /**
     * Instantiate the report controller
     */
    public function __construct()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->tableManualRepository = GeneralUtility::makeInstance(TableManualRepository::class);
    }

    /**
     * Injects the request object for the current request, and renders correct action
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $action = $request->getQueryParams()['action'] ?? $request->getParsedBody()['action'] ?? 'index';

        if ($action === 'detail') {
            $table = $request->getQueryParams()['table'] ?? $request->getParsedBody()['table'];
            if (!$table) {
                $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                return new RedirectResponse((string)$uriBuilder->buildUriFromRoute('help_cshmanual', [
                    'action' => 'index',
                ]), 303);
            }
        }

        $this->initializeView($action);

        $result = call_user_func_array([$this, $action . 'Action'], [$request]);
        if ($result instanceof ResponseInterface) {
            return $result;
        }

        $this->registerDocheaderButtons($request);

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
        $this->view->setTemplateRootPaths(['EXT:backend/Resources/Private/Templates/ContextSensitiveHelp']);
        $this->view->setPartialRootPaths(['EXT:backend/Resources/Private/Partials']);
        $this->view->setLayoutRootPaths(['EXT:backend/Resources/Private/Layouts']);
        $this->view->getRequest()->setControllerExtensionName('Backend');
        $this->view->assign('copyright', BackendUtility::TYPO3_copyRightNotice());
    }

    /**
     * Show table of contents
     */
    public function indexAction()
    {
        $this->view->assign('toc', $this->tableManualRepository->getSections(self::TOC_ONLY));
    }

    /**
     * Show the table of contents and all manuals
     */
    public function allAction()
    {
        $this->view->assign('all', $this->tableManualRepository->getSections(self::FULL));
    }

    /**
     * Show a single manual
     *
     * @param ServerRequestInterface $request
     */
    public function detailAction(ServerRequestInterface $request)
    {
        $table = $request->getQueryParams()['table'] ?? $request->getParsedBody()['table'];
        $field = $request->getQueryParams()['field'] ?? $request->getParsedBody()['field'] ?? '*';

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
            'manuals' => $field === '*'
                ? $this->tableManualRepository->getTableManual($mainKey)
                : [$this->tableManualRepository->getSingleManual($mainKey, $field)],
        ]);
    }

    /**
     * Registers the Icons into the docheader
     *
     * @param ServerRequestInterface $request
     */
    protected function registerDocheaderButtons(ServerRequestInterface $request)
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        if ($this->getBackendUser()->mayMakeShortcut()) {
            $shortcutButton = $buttonBar->makeShortcutButton()
                ->setModuleName('help_cshmanual')
                ->setGetVariables(['table', 'field', 'route']);
            $buttonBar->addButton($shortcutButton);
        }

        $action = $request->getQueryParams()['action'] ?? $request->getParsedBody()['action'] ?? 'index';
        if ($action !== 'index') {
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $backButton = $buttonBar->makeLinkButton()
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:back'))
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-view-go-up', Icon::SIZE_SMALL))
                ->setHref((string)$uriBuilder->buildUriFromRoute('help_cshmanual'));
            $buttonBar->addButton($backButton);
        }
    }

    /**
     * Returns the currently logged in BE user
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns the LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
