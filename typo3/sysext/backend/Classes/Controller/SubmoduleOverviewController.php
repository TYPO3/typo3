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

namespace TYPO3\CMS\Backend\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;

/**
 * Controller for displaying a card-based overview of available submodules.
 * This provides a user-friendly way to navigate between third-level modules.
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
#[AsController]
final readonly class SubmoduleOverviewController
{
    public function __construct(
        protected ModuleTemplateFactory $moduleTemplateFactory,
        protected ModuleProvider $moduleProvider,
        protected IconFactory $iconFactory,
        protected UriBuilder $uriBuilder,
    ) {}

    /**
     * Main action that displays the submodule overview cards
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);
        $currentModule = $request->getAttribute('module');
        if (!($currentModule instanceof ModuleInterface) || !$this->moduleProvider->accessGranted($currentModule->getIdentifier(), $this->getBackendUser())) {
            return $view->renderResponse('SubmoduleOverview/Cards');
        }

        $id = (int)($request->getParsedBody()['id'] ?? $request->getQueryParams()['id'] ?? 0);
        $pageinfo = BackendUtility::readPageAccess($id, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW)) ?: [];
        $view->setTitle(
            $this->getLanguageService()->sL($currentModule->getTitle()),
            $id !== 0 && isset($pageinfo['title']) ? $pageinfo['title'] : ''
        );
        if ($pageinfo !== []) {
            $view->getDocHeaderComponent()->setMetaInformation($pageinfo);
        }
        $view->makeDocHeaderModuleMenu(['id' => $id]);

        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
        $reloadButton = $buttonBar->makeLinkButton()
            ->setHref($request->getAttribute('normalizedParams')->getRequestUri())
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
            ->setIcon($this->iconFactory->getIcon('actions-refresh', IconSize::SMALL));
        $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT);
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier($currentModule->getIdentifier())
            ->setDisplayName($this->getLanguageService()->sL($currentModule->getTitle()));
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);

        $view->setTitle($this->getLanguageService()->sL($currentModule->getTitle()));
        $view->assign('currentModule', $currentModule);
        $view->assignMultiple([
            'additionalParameters' => array_filter(['id' => $id]),
            'submodules' => $this->getAccessibleSubmodules($currentModule),
            'moduleTitle' => $this->getLanguageService()->sL($currentModule->getTitle()),
        ]);
        return $view->renderResponse('SubmoduleOverview/Cards');
    }

    /**
     * Get all submodules the current user has access to
     *
     * @return ModuleInterface[]
     */
    protected function getAccessibleSubmodules(ModuleInterface $module): array
    {
        $accessibleSubmodules = [];
        foreach ($module->getSubModules() as $submodule) {
            // Check if the user has access to this submodule
            if ($this->moduleProvider->accessGranted($submodule->getIdentifier(), $this->getBackendUser())) {
                $accessibleSubmodules[] = $submodule;
            }
        }
        return $accessibleSubmodules;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
