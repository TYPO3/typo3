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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\Buttons\LanguageSelectorBuilder;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Recycler\Service\RecyclerService;

/**
 * Backend Module for the 'recycler' extension.
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
readonly class RecyclerModuleController
{
    public function __construct(
        protected PageRenderer $pageRenderer,
        protected ModuleTemplateFactory $moduleTemplateFactory,
        protected ComponentFactory $componentFactory,
        protected RecyclerService $recyclerService,
        protected SiteFinder $siteFinder,
        protected LanguageSelectorBuilder $languageSelectorBuilder,
        protected UriBuilder $uriBuilder,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $backendUser = $this->getBackendUser();
        $id = (int)($request->getQueryParams()['id'] ?? $request->getParsedBody()['id'] ?? 0);
        $pageRecord = BackendUtility::readPageAccess($id, $backendUser->getPagePermsClause(Permission::PAGE_SHOW)) ?: [];
        $view = $this->moduleTemplateFactory->create($request);

        $recordsPageLimit = MathUtility::forceIntegerInRange((int)($backendUser->getTSConfig()['mod.']['recycler.']['recordsPageLimit'] ?? 25), 1);
        $allowDelete = $backendUser->isAdmin() || ($backendUser->getTSConfig()['mod.']['recycler.']['allowDelete'] ?? false);
        $moduleData = $request->getAttribute('moduleData');
        $depthSelection = (int)$moduleData->get('depthSelection');
        $tableSelection = (string)$moduleData->get('tableSelection');
        $languageId = $id > 0 ? $this->addLanguageSwitcher($request, $backendUser, $view, $id) : null;

        $tables = $this->recyclerService->getAvailableTables($id, $depthSelection, $languageId);
        $result = $this->recyclerService->getDeletedRecords($id, $tableSelection, $depthSelection, '', 1, $recordsPageLimit, $languageId);

        $this->pageRenderer->addInlineSettingArray('Recycler', [
            'pagingSize' => $recordsPageLimit,
            'startUid' => $id,
            'deleteDisable' => !$allowDelete,
            'depthSelection' => (string)$depthSelection,
            'tableSelection' => $tableSelection,
            'totalItems' => $result['totalItems'],
            'language' => $languageId,
        ]);
        $this->pageRenderer->loadJavaScriptModule('@typo3/recycler/recycler.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/multi-record-selection.js');

        if (($id && $pageRecord !== []) || (!$id && $backendUser->isAdmin())) {
            $view->getDocHeaderComponent()->setPageBreadcrumb($pageRecord);
        }

        $view->setTitle(
            $this->getLanguageService()->translate('title', 'recycler.module'),
            $pageRecord['title'] ?? ''
        );

        $this->registerDocHeaderButtons($view, $id, $pageRecord);

        $view->assign('allowDelete', $allowDelete);
        $view->assign('tables', $tables);
        $view->assign('depthSelection', $depthSelection);
        $view->assign('tableSelection', $tableSelection);
        $view->assign('groupedRecords', $result['groupedRecords']);
        $view->assign('totalItems', $result['totalItems']);
        $view->assign('showTableHeader', empty($tableSelection));
        $view->assign('showTableName', $backendUser->shallDisplayDebugInformation());

        return $view->renderResponse('RecyclerModule');
    }

    protected function registerDocHeaderButtons(ModuleTemplate $view, int $id, array $pageRecord): void
    {
        $languageService = $this->getLanguageService();
        $shortcutTitle = sprintf(
            '%s: %s [%d]',
            $languageService->translate('title', 'recycler.module'),
            BackendUtility::getRecordTitle('pages', $pageRecord),
            $id
        );
        $view->getDocHeaderComponent()->setShortcutContext(
            'recycler',
            $shortcutTitle,
            ['id' => $id]
        );
    }

    /**
     * Adds the language selector to the doc header, offering all site languages
     * available to the user, whether or not the page has a translation in them.
     *
     * @return int|null The selected language ID, null if records are not restricted by language
     */
    protected function addLanguageSwitcher(
        ServerRequestInterface $request,
        BackendUserAuthentication $backendUser,
        ModuleTemplate $view,
        ?int $pageId,
    ): ?int {
        try {
            $site = $this->siteFinder->getSiteByPageId($pageId);
        } catch (SiteNotFoundException) {
            $site = $request->getAttribute('site');
        }

        $availableLanguages = $site->getAvailableLanguages($backendUser, false, $pageId);

        if (count($availableLanguages) < 2) {
            // With only one language present, the dropdown is useless.
            // Switch to present all unfiltered recycler records.
            return null;
        }

        // empty string -> "all languages".
        $persistedLanguage = (string)$request->getAttribute('moduleData')->get('language', '');
        $languageId = $persistedLanguage === '' ? null : (int)$persistedLanguage;
        if ($languageId !== null && !isset($availableLanguages[$languageId])) {
            // If no selected language was found we revert to "show all entries"
            $languageId = null;
        }

        $returnUrl = GeneralUtility::sanitizeLocalUrl($request->getParsedBody()['returnUrl'] ?? $request->getQueryParams()['returnUrl'] ?? '', $request);
        $view->getDocHeaderComponent()->setLanguageSelector(
            $this->languageSelectorBuilder->buildForSiteLanguages(
                $availableLanguages,
                $languageId,
                fn(?int $languageId): string => (string)$this->uriBuilder->buildUriFromRoute('recycler', [
                    'id' => $pageId,
                    'language' => $languageId ?? '',
                    'returnUrl' => $returnUrl,
                ])
            )
        );

        return $languageId;
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
