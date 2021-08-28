<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Impexp\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Main script class for the Import / Export facility.
 *
 * @internal This class is not considered part of the public TYPO3 API.
 */
abstract class ImportExportController
{
    /**
     * Page id
     *
     * @var int
     */
    protected $id;

    /**
     * Page record of page id
     *
     * @var array
     */
    protected $pageInfo;

    /**
     * A WHERE clause for selection records from the pages table based on read-permissions of the current backend user.
     *
     * @var string
     */
    protected $permsClause;

    /**
     * @var string
     */
    protected $routeName = '';

    /**
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * Return URL of list module
     *
     * @var string
     */
    protected $returnUrl;

    /**
     * @var LanguageService
     */
    protected $lang;

    /**
     * @var StandaloneView
     */
    protected $standaloneView;

    protected IconFactory $iconFactory;
    protected PageRenderer $pageRenderer;
    protected UriBuilder $uriBuilder;
    protected ModuleTemplateFactory $moduleTemplateFactory;

    /**
     * Constructor
     */
    public function __construct(
        IconFactory $iconFactory,
        PageRenderer $pageRenderer,
        UriBuilder $uriBuilder,
        ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->iconFactory = $iconFactory;
        $this->pageRenderer = $pageRenderer;
        $this->uriBuilder = $uriBuilder;
        $this->moduleTemplateFactory = $moduleTemplateFactory;

        $templatePath = ExtensionManagementUtility::extPath('impexp') . 'Resources/Private/';

        $this->standaloneView = GeneralUtility::makeInstance(StandaloneView::class);
        $this->standaloneView->setTemplateRootPaths([$templatePath . 'Templates/ImportExport/']);
        $this->standaloneView->setLayoutRootPaths([$templatePath . 'Layouts/']);
        $this->standaloneView->setPartialRootPaths([$templatePath . 'Partials/']);
        $this->standaloneView->getRequest()->setControllerExtensionName('impexp');

        $this->permsClause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));
        $this->lang = $this->getLanguageService();
        $this->lang->includeLLFile('EXT:impexp/Resources/Private/Language/locallang.xlf');
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    abstract public function mainAction(ServerRequestInterface $request): ResponseInterface;

    /**
     * @param ServerRequestInterface $request
     * @throws RouteNotFoundException
     */
    protected function main(ServerRequestInterface $request): void
    {
        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();

        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $this->moduleTemplate->setModuleName('');

        // Checking page access
        $this->id = (int)($parsedBody['id'] ?? $queryParams['id'] ?? 0);
        $this->pageInfo = BackendUtility::readPageAccess($this->id, $this->permsClause) ?: [];

        if ($this->pageInfo === []) {
            throw new \RuntimeException(
                'You don\'t have access to this page.',
                1604308205
            );
        }

        // Setting up the module meta data:
        $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($this->pageInfo);

        // Setting up the context sensitive menu:
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Impexp/ImportExport');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Element/ImmediateActionElement');

        $this->standaloneView->assign('moduleUrl', (string)$this->uriBuilder->buildUriFromRoute($this->routeName));
        $this->standaloneView->assign('id', $this->id);
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     */
    protected function registerDocHeaderButtons(): void
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        // back button
        if ($this->returnUrl) {
            $backButton = $buttonBar->makeLinkButton()
                ->setHref($this->returnUrl)
                ->setTitle($this->lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
                ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
            $buttonBar->addButton($backButton);
        }
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
