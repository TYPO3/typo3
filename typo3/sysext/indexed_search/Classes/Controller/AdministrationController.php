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

namespace TYPO3\CMS\IndexedSearch\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\AllowedMethodsTrait;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\IndexedSearch\Domain\Repository\AdministrationRepository;
use TYPO3\CMS\IndexedSearch\FileContentParser;
use TYPO3\CMS\IndexedSearch\Indexer;

/**
 * Administration controller. Main module "Indexing".
 *
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class AdministrationController extends ActionController
{
    use AllowedMethodsTrait;

    protected int $pageUid = 0;
    protected array $indexerConfig = [];

    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly AdministrationRepository $administrationRepository,
        protected readonly Indexer $indexer,
        protected readonly IconFactory $iconFactory,
        protected readonly ExtensionConfiguration $extensionConfiguration,
        protected readonly ConnectionPool $connectionPool,
    ) {}

    /**
     * Generates the action menu
     */
    protected function initializeModuleTemplate(ServerRequestInterface $request): ModuleTemplate
    {
        $languageService = $this->getLanguageService();
        $menuItems = [
            'statistic' => [
                'controller' => 'Administration',
                'action' => 'statistic',
                'label' => $languageService->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang.xlf:administration.menu.statistic'),
            ],
            'pages' => [
                'controller' => 'Administration',
                'action' => 'pages',
                'label' => $languageService->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang.xlf:administration.menu.pages'),
            ],
            'externalDocuments' => [
                'controller' => 'Administration',
                'action' => 'externalDocuments',
                'label' => $languageService->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang.xlf:administration.menu.externalDocuments'),
            ],
            'index' => [
                'controller' => 'Administration',
                'action' => 'index',
                'label' => $languageService->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang.xlf:administration.menu.general'),
            ],
        ];

        $view = $this->moduleTemplateFactory->create($request);

        $menu = $view->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('IndexedSearchModuleMenu');
        $menu->setLabel(
            $languageService->sL(
                'LLL:EXT:backend/Resources/Private/Language/locallang.xlf:moduleMenu.dropdown.label'
            )
        );

        $context = '';
        foreach ($menuItems as $menuItemConfig) {
            $isActive = $this->request->getControllerActionName() === $menuItemConfig['action'];
            $menuItem = $menu->makeMenuItem()
                ->setTitle($menuItemConfig['label'])
                ->setHref($this->uriBuilder->reset()->uriFor($menuItemConfig['action'], [], $menuItemConfig['controller']))
                ->setActive($isActive);
            $menu->addMenuItem($menuItem);
            if ($isActive) {
                $context = $menuItemConfig['label'];
            }
        }

        $view->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
        $view->setTitle(
            $languageService->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'),
            $context
        );

        $permissionClause = $this->getBackendUserAuthentication()->getPagePermsClause(Permission::PAGE_SHOW);
        $pageRecord = BackendUtility::readPageAccess($this->pageUid, $permissionClause);
        if ($pageRecord) {
            $view->getDocHeaderComponent()->setPageBreadcrumb($pageRecord);
        }
        $view->setFlashMessageQueue($this->getFlashMessageQueue());

        return $view;
    }

    /**
     * Function will be called before every other action
     */
    protected function initializeAction(): void
    {
        $this->pageUid = (int)($this->request->getQueryParams()['id'] ?? 0);
        $this->indexerConfig = $this->extensionConfiguration->get('indexed_search') ?? [];
        parent::initializeAction();
    }

    /**
     * Override the action name if found in the uc of the user
     */
    public function processRequest(RequestInterface $request): ResponseInterface
    {
        $arguments = $request->getArguments();
        $beUser = $this->getBackendUserAuthentication();

        if (isset($arguments['action']) && method_exists($this, $arguments['action'] . 'Action')) {
            $action = $arguments['action'];
            switch ($action) {
                case 'saveStopwords':
                    $action = 'statisticDetails';
                    break;
                case 'deleteIndexedItem':
                    $action = 'statistic';
                    break;
            }
            $beUser->uc['indexed_search']['action'] = $action;
            $beUser->uc['indexed_search']['arguments'] = $arguments;
            $beUser->writeUC();
        } elseif (isset($beUser->uc['indexed_search']['action'])) {
            $request = $request->withControllerActionName((string)$beUser->uc['indexed_search']['action']);
            if (isset($beUser->uc['indexed_search']['arguments'])) {
                $request = $request->withArguments($beUser->uc['indexed_search']['arguments']);
            }
        }

        return parent::processRequest($request);
    }

    /**
     * Index action contains the most important statistics
     */
    protected function indexAction(): ResponseInterface
    {
        $view = $this->initializeModuleTemplate($this->request);
        $view->assignMultiple([
            'records' => $this->administrationRepository->getRecordsNumbers(),
            'phash' => $this->administrationRepository->getPageHashTypes(),
        ]);
        if ($this->pageUid) {
            $expressionBuilder = $this->connectionPool->getQueryBuilderForTable('index_stat_word')->expr();
            $last24hours = $expressionBuilder->gt('tstamp', $GLOBALS['EXEC_TIME'] - 86400);
            $last30days = $expressionBuilder->gt('tstamp', $GLOBALS['EXEC_TIME'] - 30 * 86400);
            $view->assignMultiple([
                'extensionConfiguration' => $this->indexerConfig,
                'pageUid' => $this->pageUid,
                'all' => $this->administrationRepository->getGeneralSearchStatistic('', $this->pageUid),
                'last24hours' => $this->administrationRepository->getGeneralSearchStatistic($last24hours, $this->pageUid),
                'last30days' => $this->administrationRepository->getGeneralSearchStatistic($last30days, $this->pageUid),
            ]);
        }
        return $view->renderResponse('Administration/Index');
    }

    /**
     * Statistics for pages
     */
    protected function pagesAction(): ResponseInterface
    {
        $view = $this->initializeModuleTemplate($this->request);
        $view->assignMultiple([
            'extensionConfiguration' => $this->indexerConfig,
            'records' => $this->administrationRepository->getPageStatistic(),
            'pageUid' => $this->pageUid,
        ]);
        return $view->renderResponse('Administration/Pages');
    }

    /**
     * Statistics for external documents
     */
    protected function externalDocumentsAction(): ResponseInterface
    {
        $view = $this->initializeModuleTemplate($this->request);
        $view->assignMultiple([
            'extensionConfiguration' => $this->indexerConfig,
            'records' => $this->administrationRepository->getExternalDocumentsStatistic(),
            'pageUid' => $this->pageUid,
        ]);
        return $view->renderResponse('Administration/ExternalDocuments');
    }

    /**
     * Statistics for a given page hash
     */
    protected function statisticDetailsAction(string $pageHash): ResponseInterface
    {
        $view = $this->initializeModuleTemplate($this->request);
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();

        // Set back button
        $backButton = $buttonBar
            ->makeLinkButton()
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang.xlf:administration.back'))
            ->setShowLabelText(true)
            ->setIcon($this->iconFactory->getIcon('actions-view-go-back', IconSize::SMALL))
            ->setHref($this->uriBuilder->reset()->uriFor('statistic', [], 'Administration'));
        $buttonBar->addButton($backButton);

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('index_phash');
        $pageHashRow = $queryBuilder
            ->select('*')
            ->from('index_phash')
            ->where(
                $queryBuilder->expr()->eq(
                    'phash',
                    $queryBuilder->createNamedParameter($pageHash, Connection::PARAM_STR)
                )
            )
            ->executeQuery()
            ->fetchAssociative();

        if (!is_array($pageHashRow)) {
            return $this->redirect('statistic');
        }

        $pageRecord = BackendUtility::getRecord('pages', $pageHashRow['data_page_id']);

        // words
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('index_words');
        $wordRecords = $queryBuilder
            ->select('index_words.*', 'index_rel.*')
            ->from('index_words')
            ->from('index_rel')
            ->where(
                $queryBuilder->expr()->eq(
                    'index_rel.phash',
                    $queryBuilder->createNamedParameter($pageHash, Connection::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'index_words.wid',
                    $queryBuilder->quoteIdentifier('index_rel.wid')
                )
            )
            ->orderBy('index_words.baseword')
            ->executeQuery()
            ->fetchAllAssociative();

        // sections
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('index_section');
        $sections = $queryBuilder
            ->select('*')
            ->from('index_section')
            ->where(
                $queryBuilder->expr()->eq(
                    'phash',
                    $queryBuilder->createNamedParameter($pageHash, Connection::PARAM_STR)
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();

        $view->assignMultiple([
            'extensionConfiguration' => $this->indexerConfig,
            'phash' => $pageHash,
            'phashRow' => $pageHashRow,
            'words' => $wordRecords,
            'sections' => $sections,
            'page' => $pageRecord,
        ]);

        return $view->renderResponse('Administration/StatisticDetails');
    }

    protected function initializeSaveStopwordsAction(): void
    {
        $this->assertAllowedHttpMethod($this->request, 'POST');
    }

    /**
     * Save stop words
     */
    protected function saveStopwordsAction(string $pageHash, array $stopwords = []): ResponseInterface
    {
        if ($this->getBackendUserAuthentication()->isAdmin()) {
            if (is_array($stopwords) && !empty($stopwords)) {
                $this->administrationRepository->saveStopWords($stopwords);
            }
        }
        return $this->redirect('statisticDetails', null, null, ['pageHash' => $pageHash]);
    }

    /**
     * Statistics for a given word id
     */
    protected function wordDetailAction(string $wordHash, string $pageHash, string $wordTitle): ResponseInterface
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('index_phash');
        $rows = $queryBuilder
            ->select('index_phash.*', 'index_section.*', 'index_rel.*')
            ->from('index_rel')
            ->from('index_section')
            ->from('index_phash')
            ->where(
                $queryBuilder->expr()->eq(
                    'index_rel.wid',
                    $queryBuilder->createNamedParameter($wordHash, Connection::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'index_rel.phash',
                    $queryBuilder->quoteIdentifier('index_section.phash')
                ),
                $queryBuilder->expr()->eq(
                    'index_section.phash',
                    $queryBuilder->quoteIdentifier('index_phash.phash')
                )
            )
            ->orderBy('index_rel.freq', 'desc')
            ->executeQuery()
            ->fetchAllAssociative();

        $view = $this->initializeModuleTemplate($this->request);
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();

        // Set back button
        $backButton = $buttonBar
            ->makeLinkButton()
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang.xlf:administration.back'))
            ->setShowLabelText(true)
            ->setIcon($this->iconFactory->getIcon('actions-view-go-back', IconSize::SMALL))
            ->setHref($this->uriBuilder->reset()->uriFor('statisticDetails', ['pageHash' => $pageHash], 'Administration'));
        $buttonBar->addButton($backButton);

        $view->assignMultiple([
            'extensionConfiguration' => $this->indexerConfig,
            'rows' => $rows,
            'phash' => $pageHash,
            'wordTitle' => $wordTitle,
        ]);
        return $view->renderResponse('Administration/WordDetail');
    }

    /**
     * General statistics
     */
    protected function statisticAction(int $depth = 1, string $mode = 'overview'): ResponseInterface
    {
        $externalParsers = [];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['external_parsers'] ?? [] as $extension => $className) {
            /** @var FileContentParser $fileContentParser */
            $fileContentParser = GeneralUtility::makeInstance($className);
            if ($fileContentParser->softInit($extension)) {
                $externalParsers[$extension] = $fileContentParser;
            }
        }
        $this->administrationRepository->external_parsers = $externalParsers;
        $tree = $this->administrationRepository->getTree($this->pageUid, $depth, $mode);
        $view = $this->initializeModuleTemplate($this->request);
        $view->assignMultiple([
            'extensionConfiguration' => $this->indexerConfig,
            'levelTranslations' => explode('|', $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.enterSearchLevels')),
            'tree' => $tree,
            'pageUid' => $this->pageUid,
            'mode' => $mode,
            'depth' => $depth,
            'backendUserTitleLength' => (int)$this->getBackendUserAuthentication()->uc['titleLen'],
        ]);
        return $view->renderResponse('Administration/Statistic');
    }

    protected function initializeDeleteIndexedItemAction(): void
    {
        $this->assertAllowedHttpMethod($this->request, 'POST');
    }

    /**
     * Remove item from index
     */
    protected function deleteIndexedItemAction(string $itemId, int $depth = 1, string $mode = 'overview'): ResponseInterface
    {
        $this->administrationRepository->removeIndexedPhashRow($itemId, $this->pageUid, $depth);
        return $this->redirect('statistic', null, null, ['depth' => $depth, 'mode' => $mode]);
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
