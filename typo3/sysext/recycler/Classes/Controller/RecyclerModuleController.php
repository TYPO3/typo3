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

namespace TYPO3\CMS\Recycler\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Backend Module for the 'recycler' extension.
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
readonly class RecyclerModuleController
{
    public function __construct(
        protected IconFactory $iconFactory,
        protected PageRenderer $pageRenderer,
        protected ModuleTemplateFactory $moduleTemplateFactory,
        protected ComponentFactory $componentFactory,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $backendUser = $this->getBackendUser();
        $id = (int)($request->getQueryParams()['id'] ?? $request->getParsedBody()['id'] ?? 0);
        $pageRecord = BackendUtility::readPageAccess($id, $backendUser->getPagePermsClause(Permission::PAGE_SHOW)) ?: [];
        $view = $this->moduleTemplateFactory->create($request);

        // read configuration
        $recordsPageLimit = MathUtility::forceIntegerInRange((int)($backendUser->getTSConfig()['mod.']['recycler.']['recordsPageLimit'] ?? 25), 1);
        $allowDelete = $backendUser->isAdmin() || ($backendUser->getTSConfig()['mod.']['recycler.']['allowDelete'] ?? false);
        $sessionData = $backendUser->uc['tx_recycler'] ?? [];

        $this->pageRenderer->addInlineSettingArray('Recycler', [
            'pagingSize' => $recordsPageLimit,
            'startUid' => $id,
            'deleteDisable' => !$allowDelete,
            'depthSelection' => ($sessionData['depthSelection'] ?? false) ?: '0',
            'tableSelection' => ($sessionData['tableSelection'] ?? false) ?: '',
        ]);
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:recycler/Resources/Private/Language/locallang.xlf');
        $this->pageRenderer->loadJavaScriptModule('@typo3/recycler/recycler.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/multi-record-selection.js');

        if (($id && $pageRecord !== []) || (!$id && $backendUser->isAdmin())) {
            $view->getDocHeaderComponent()->setPageBreadcrumb($pageRecord);
        }

        $view->setTitle(
            $this->getLanguageService()->sL('LLL:EXT:recycler/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'),
            $pageRecord['title'] ?? ''
        );

        $this->registerDocHeaderButtons($view, $id, $pageRecord);

        $view->assign('allowDelete', $allowDelete);

        return $view->renderResponse('RecyclerModule');
    }

    /**
     * Registers doc header buttons.
     */
    protected function registerDocHeaderButtons(ModuleTemplate $view, int $id, array $pageRecord): void
    {
        $languageService = $this->getLanguageService();
        $shortcutTitle = sprintf(
            '%s: %s [%d]',
            $languageService->sL('LLL:EXT:recycler/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'),
            BackendUtility::getRecordTitle('pages', $pageRecord),
            $id
        );
        $shortcutButton = $this->componentFactory->createShortcutButton()
            ->setRouteIdentifier('recycler')
            ->setDisplayName($shortcutTitle)
            ->setArguments(['id' => $id]);
        $view->addButtonToButtonBar($shortcutButton);
        $view->addButtonToButtonBar($this->componentFactory->createReloadButton('#')->setDataAttributes(['action' => 'reload']), ButtonBar::BUTTON_POSITION_RIGHT);
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
