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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
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
    protected int $pageUid = 0;
    protected array $indexerConfig = [];

    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly AdministrationRepository $administrationRepository,
        protected readonly Indexer $indexer,
        protected readonly IconFactory $iconFactory,
        protected readonly ExtensionConfiguration $extensionConfiguration,
        protected readonly ConnectionPool $connectionPool,
    ) {
    }

    /**
     * Generates the action menu
     */
    protected function initializeModuleTemplate(ServerRequestInterface $request): ModuleTemplate
    {
        $menuItems = [
            'statistic' => [
                'controller' => 'Administration',
                'action' => 'statistic',
                'label' => $this->getLanguageService()->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang.xlf:administration.menu.statistic'),
            ],
            'pages' => [
                'controller' => 'Administration',
                'action' => 'pages',
                'label' => $this->getLanguageService()->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang.xlf:administration.menu.pages'),
            ],
            'externalDocuments' => [
                'controller' => 'Administration',
                'action' => 'externalDocuments',
                'label' => $this->getLanguageService()->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang.xlf:administration.menu.externalDocuments'),
            ],
            'index' => [
                'controller' => 'Administration',
                'action' => 'index',
                'label' => $this->getLanguageService()->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang.xlf:administration.menu.general'),
            ],
        ];

        $view = $this->moduleTemplateFactory->create($request);

        $menu = $view->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('IndexedSearchModuleMenu');

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
            $this->getLanguageService()->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'),
            $context
        );

        $permissionClause = $this->getBackendUserAuthentication()->getPagePermsClause(Permission::PAGE_SHOW);
        $pageRecord = BackendUtility::readPageAccess($this->pageUid, $permissionClause);
        if ($pageRecord) {
            $view->getDocHeaderComponent()->setMetaInformation($pageRecord);
        }
        $view->setFlashMessageQueue($this->getFlashMessageQueue());

        return $view;
    }

    /**
     * Function will be called before every other action
     */
    protected function initializeAction()
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

        if (is_array($arguments) && isset($arguments['action']) && method_exists($this, $arguments['action'] . 'Action')) {
            $action = $arguments['action'];
            switch ($action) {
                case 'saveStopwordsKeywords':
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
        ]);
        return $view->renderResponse('Administration/ExternalDocuments');
    }

    /**
     * Statistics for a given page hash
     *
     * @param int $pageHash
     */
    protected function statisticDetailsAction($pageHash = 0): ResponseInterface
    {
        $view = $this->initializeModuleTemplate($this->request);
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
        $pageHash = (int)$pageHash;

        // Set back button
        $backButton = $buttonBar
            ->makeLinkButton()
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang.xlf:administration.back'))
            ->setIcon($this->iconFactory->getIcon('actions-view-go-up', Icon::SIZE_SMALL))
            ->setHref($this->uriBuilder->reset()->uriFor('statistic', [], 'Administration'));
        $buttonBar->addButton($backButton);

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('index_phash');
        $pageHashRow = $queryBuilder
            ->select('*')
            ->from('index_phash')
            ->where(
                $queryBuilder->expr()->eq(
                    'phash',
                    $queryBuilder->createNamedParameter($pageHash, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAssociative();

        if (!is_array($pageHashRow)) {
            return $this->redirect('statistic');
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('index_debug');
        $debugRow = $queryBuilder
            ->select('debuginfo')
            ->from('index_debug')
            ->where(
                $queryBuilder->expr()->eq(
                    'phash',
                    $queryBuilder->createNamedParameter($pageHash, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAssociative();
        $debugInfo = [];
        $lexer = '';
        if (is_array($debugRow)) {
            $debugInfo = json_decode($debugRow['debuginfo'], true);
            $lexer = $debugInfo['lexer'];
            unset($debugInfo['lexer']);
        }
        $pageRecord = BackendUtility::getRecord('pages', $pageHashRow['data_page_id']);
        $keywords = is_array($pageRecord) ? array_flip(GeneralUtility::trimExplode(',', (string)$pageRecord['keywords'], true)) : [];

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('index_words');
        $wordRecords = $queryBuilder
            ->select('index_words.*', 'index_rel.*')
            ->from('index_words')
            ->from('index_rel')
            ->where(
                $queryBuilder->expr()->eq(
                    'index_rel.phash',
                    $queryBuilder->createNamedParameter($pageHash, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'index_words.wid',
                    $queryBuilder->quoteIdentifier('index_rel.wid')
                )
            )
            ->orderBy('index_words.baseword')
            ->executeQuery()
            ->fetchAllAssociative();
        foreach ($wordRecords as $id => $row) {
            if (isset($keywords[$row['baseword']])) {
                $wordRecords[$id]['is_keyword'] = true;
            }
        }
        $metaphoneRows = $metaphone = [];
        $enableMetaphoneSearch = (bool)($this->indexerConfig['enableMetaphoneSearch'] ?? false);
        if ($enableMetaphoneSearch && is_array($wordRecords)) {
            // Group metaphone hash
            foreach ($wordRecords as $row) {
                $metaphoneRows[$row['metaphone']][] = $row['baseword'];
            }

            foreach ($metaphoneRows as $hash => $words) {
                if (count($words) > 1) {
                    $metaphone[] = [
                        'metaphone' => $this->indexer->metaphone($words[0], true), $hash,
                        'words' => $words,
                        'hash' => $hash,
                    ];
                }
            }
        }

        // sections
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('index_section');
        $sections = $queryBuilder
            ->select('*')
            ->from('index_section')
            ->where(
                $queryBuilder->expr()->eq(
                    'phash',
                    $queryBuilder->createNamedParameter($pageHash, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();

        // top words
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('index_words');
        $topCountWords = $queryBuilder
            ->select('index_words.baseword', 'index_words.metaphone', 'index_rel.*')
            ->from('index_words')
            ->from('index_rel')
            ->setMaxResults(20)
            ->where(
                $queryBuilder->expr()->eq(
                    'index_rel.phash',
                    $queryBuilder->createNamedParameter($pageHash, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'index_words.is_stopword',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'index_words.wid',
                    $queryBuilder->quoteIdentifier('index_rel.wid')
                )
            )
            ->orderBy('index_rel.count', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();

        // top frequency
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('index_words');
        $topFrequency = $queryBuilder
            ->select('index_words.baseword', 'index_words.metaphone', 'index_rel.*')
            ->from('index_words')
            ->from('index_rel')
            ->setMaxResults(20)
            ->where(
                $queryBuilder->expr()->eq(
                    'index_rel.phash',
                    $queryBuilder->createNamedParameter($pageHash, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'index_words.is_stopword',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'index_words.wid',
                    $queryBuilder->quoteIdentifier('index_rel.wid')
                )
            )
            ->orderBy('index_rel.freq', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();

        $view->assignMultiple([
            'extensionConfiguration' => $this->indexerConfig,
            'phash' => (int)$pageHash,
            'phashRow' => $pageHashRow,
            'words' => $wordRecords,
            'sections' => $sections,
            'topCount' => $topCountWords,
            'topFrequency' => $topFrequency,
            'debug' => $debugInfo,
            'lexer' => $lexer,
            'metaphone' => $metaphone,
            'page' => $pageRecord,
            'keywords' => $keywords,
        ]);

        return $view->renderResponse('Administration/StatisticDetails');
    }

    /**
     * Save stop words and keywords
     *
     * @param string $pageHash
     * @param int $pageId
     * @param array $stopwords
     * @param array $keywords
     */
    protected function saveStopwordsKeywordsAction($pageHash, $pageId, $stopwords = [], $keywords = []): ResponseInterface
    {
        if ($this->getBackendUserAuthentication()->isAdmin()) {
            if (is_array($stopwords) && !empty($stopwords)) {
                $this->administrationRepository->saveStopWords($stopwords);
            }
            if (is_array($keywords) && !empty($keywords)) {
                $this->administrationRepository->saveKeywords($keywords, $pageId);
            }
        }
        return $this->redirect('statisticDetails', null, null, ['pageHash' => $pageHash]);
    }

    /**
     * Statistics for a given word id
     *
     * @param int $id
     * @param int $pageHash
     */
    protected function wordDetailAction($id = 0, $pageHash = 0): ResponseInterface
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
                    $queryBuilder->createNamedParameter($id, Connection::PARAM_INT)
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
        $view->assignMultiple([
            'extensionConfiguration' => $this->indexerConfig,
            'rows' => $rows,
            'phash' => $pageHash,
        ]);
        return $view->renderResponse('Administration/WordDetail');
    }

    /**
     * General statistics
     *
     * @param int $depth
     * @param string $mode
     */
    protected function statisticAction($depth = 1, $mode = 'overview'): ResponseInterface
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
        $allLines = $this->administrationRepository->getTree($this->pageUid, $depth, $mode);
        $view = $this->initializeModuleTemplate($this->request);
        $view->assignMultiple([
            'extensionConfiguration' => $this->indexerConfig,
            'levelTranslations' => explode('|', $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.enterSearchLevels')),
            'tree' => $allLines,
            'pageUid' => $this->pageUid,
            'mode' => $mode,
            'depth' => $depth,
        ]);
        return $view->renderResponse('Administration/Statistic');
    }

    /**
     * Remove item from index
     *
     * @param string $itemId
     * @param int $depth
     * @param string $mode
     */
    protected function deleteIndexedItemAction($itemId, $depth = 1, $mode = 'overview'): ResponseInterface
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
