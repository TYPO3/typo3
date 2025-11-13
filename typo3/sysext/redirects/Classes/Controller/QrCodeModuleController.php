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

namespace TYPO3\CMS\Redirects\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\Components\MultiRecordSelection\Action;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Redirects\Repository\Demand;
use TYPO3\CMS\Redirects\Repository\RedirectRepository;
use TYPO3\CMS\Redirects\Service\ModulePaginationService;
use TYPO3\CMS\Redirects\Utility\RedirectConflict;

/**
 * Lists all QR Codes in the TYPO3 Backend as a module.
 *
 * @internal This class is a specific TYPO3 Backend controller implementation and is not part of the Public TYPO3 API.
 */
#[AsController]
class QrCodeModuleController
{
    public function __construct(
        protected UriBuilder $uriBuilder,
        protected IconFactory $iconFactory,
        protected RedirectRepository $redirectRepository,
        protected ModuleTemplateFactory $moduleTemplateFactory,
        protected ComponentFactory $componentFactory,
        protected ModulePaginationService $modulePaginationService,
    ) {}

    /**
     * Injects the request object for the current request, and renders the overview of all QR Codes
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);
        $demand = Demand::fromRequest($request);
        $redirectType = $demand->getRedirectType();

        $view->setTitle(
            $this->getLanguageService()->translate('title', 'redirects.modules.qrcodes')
        );

        $view->makeDocHeaderModuleMenu();
        $this->registerDocHeaderButtons($view);

        $requestUri = $request->getAttribute('normalizedParams')->getRequestUri();
        $languageService = $this->getLanguageService();
        $pagination = $this->modulePaginationService->preparePagination($demand);
        $view->assignMultiple([
            'redirects' => $this->redirectRepository->findRedirectsByDemand($demand),
            'hosts' => $this->redirectRepository->findHostsOfRedirects($redirectType),
            'defaultIntegrityStatus' => RedirectConflict::NO_CONFLICT,
            'demand' => $demand,
            'showHitCounter' => GeneralUtility::makeInstance(Features::class)->isFeatureEnabled('redirects.hitCount'),
            'pagination' => $pagination,
            'returnUrl' => $this->uriBuilder->buildUriFromRoute('qrcodes', [
                'page' => $pagination['current'],
                'demand' =>  $demand->getParameters(),
                'orderField' => $demand->getOrderField(),
                'orderDirection' => $demand->getOrderDirection(),
            ]),
            'actions' => [
                new Action(
                    'edit',
                    [
                        'idField' => 'uid',
                        'tableName' => 'sys_redirect',
                        'returnUrl' => $requestUri,
                    ],
                    'actions-open',
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.edit'
                ),
                new Action(
                    'delete',
                    [
                        'idField' => 'uid',
                        'tableName' => 'sys_redirect',
                        'title' => $languageService->translate('delete.title', 'redirects.modules.qrcodes'),
                        'content' => $languageService->translate('delete.message', 'redirects.modules.qrcodes'),
                        'ok' => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.delete'),
                        'cancel' => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.cancel'),
                        'returnUrl' => $requestUri,
                    ],
                    'actions-edit-delete',
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.delete'
                ),
            ],
        ]);
        return $view->renderResponse('QrCode/Overview');
    }

    /**
     * Create document header buttons for QR codes
     */
    protected function registerDocHeaderButtons(ModuleTemplate $view): void
    {
        $languageService = $this->getLanguageService();

        // Create new
        $newRecordButton = $this->componentFactory->createLinkButton()
            ->setHref((string)$this->uriBuilder->buildUriFromRoute(
                'record_edit',
                [
                    'edit' => ['sys_redirect' => ['new']],
                    'module' => 'qrcodes',
                    'defVals' => [
                        'sys_redirect' => [
                            'redirect_type' => Demand::QRCODE_REDIRECT_TYPE,
                        ],
                    ],
                    'returnUrl' => (string)$this->uriBuilder->buildUriFromRoute('qrcodes'),
                ]
            ))
            ->setTitle($languageService->translate('add_text', 'redirects.modules.qrcodes'))
            ->setShowLabelText(true)
            ->setIcon($this->iconFactory->getIcon('actions-plus', IconSize::SMALL));
        $view->addButtonToButtonBar($newRecordButton, ButtonBar::BUTTON_POSITION_LEFT, 10);

        $view->getDocHeaderComponent()->setShortcutContext(
            'qrcodes',
            $languageService->translate('short_description', 'redirects.modules.qrcodes')
        );
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
