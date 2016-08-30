<?php
namespace TYPO3\CMS\IndexedSearch\Controller;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Request as WebRequest;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\IndexedSearch\Domain\Repository\AdministrationRepository;
use TYPO3\CMS\IndexedSearch\Indexer;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Administration controller
 */
class AdministrationController extends ActionController
{
    /**
     * @var AdministrationRepository
     */
    protected $administrationRepository;

    /**
     * @var int Current page id
     */
    protected $pageUid = 0;

    /**
     * @var array External parsers
     */
    protected $external_parsers = [];

    /**
     * @var array Configuration defined in the Extension Manager
     */
    protected $indexerConfig = [];

    /**
     * @var bool is metaphone enabled
     */
    protected $enableMetaphoneSearch = false;

    /**
     * Indexer object
     *
     * @var \TYPO3\CMS\IndexedSearch\Indexer
     */
    protected $indexer;

    /**
     * Backend Template Container
     *
     * @var BackendTemplateView
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * BackendTemplateContainer
     *
     * @var BackendTemplateView
     */
    protected $view;

    /**
     * Set up the doc header properly here
     *
     * @param ViewInterface $view
     */
    protected function initializeView(ViewInterface $view)
    {
        if ($view instanceof BackendTemplateView) {
            /** @var BackendTemplateView $view */
            parent::initializeView($view);
            $permissionClause = $this->getBackendUserAuthentication()->getPagePermsClause(1);
            $pageRecord = BackendUtility::readPageAccess($this->pageUid, $permissionClause);
            if ($pageRecord) {
                $view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation($pageRecord);
            }
            $this->generateMenu();
            $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());
        }
    }

    /**
     * Generates the action menu
     */
    protected function generateMenu()
    {
        $menuItems = [
            'index' => [
                'controller' => 'Administration',
                'action' => 'index',
                'label' => $this->getLanguageService()->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang.xml:administration.menu.general')
            ],
            'pages' => [
                'controller' => 'Administration',
                'action' => 'pages',
                'label' => $this->getLanguageService()->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang.xml:administration.menu.pages')
            ],
            'externalDocuments' => [
                'controller' => 'Administration',
                'action' => 'externalDocuments',
                'label' => $this->getLanguageService()->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang.xml:administration.menu.externalDocuments')
            ],
            'statistic' => [
                'controller' => 'Administration',
                'action' => 'statistic',
                'label' => $this->getLanguageService()->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang.xml:administration.menu.statistic')
            ]
        ];
        $uriBuilder = $this->objectManager->get(UriBuilder::class);
        $uriBuilder->setRequest($this->request);

        $menu = $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('IndexedSearchModuleMenu');

        foreach ($menuItems as $menuItemConfig) {
            $isActive = $this->request->getControllerActionName() === $menuItemConfig['action'];
            $menuItem = $menu->makeMenuItem()
                ->setTitle($menuItemConfig['label'])
                ->setHref($this->getHref($menuItemConfig['controller'], $menuItemConfig['action']))
                ->setActive($isActive);
            $menu->addMenuItem($menuItem);
        }

        $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    /**
     * Function will be called before every other action
     *
     * @return void
     */
    public function initializeAction()
    {
        $this->pageUid = (int)GeneralUtility::_GET('id');
        $this->indexerConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['indexed_search']);
        $this->enableMetaphoneSearch = (bool)$this->indexerConfig['enableMetaphoneSearch'];
        $this->indexer = GeneralUtility::makeInstance(Indexer::class);

        parent::initializeAction();
    }

    /**
     * Override the action name if found in the uc of the user
     *
     * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request
     * @param \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function processRequest(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request, \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response)
    {
        $vars = GeneralUtility::_GET('tx_indexedsearch_web_indexedsearchisearch');

        $beUser = $this->getBackendUserAuthentication();
        if (is_array($vars) && isset($vars['action']) && method_exists($this, $vars['action'] . 'Action')) {
            $action = $vars['action'];

            switch ($action) {
                case 'saveStopwordsKeywords':
                    $action = 'statisticDetails';
                    break;
                case 'deleteIndexedItem':
                    $action = 'statistic';
                    break;
            }

            $beUser->uc['indexed_search']['action'] = $action;
            $beUser->uc['indexed_search']['arguments'] = $request->getArguments();
            $beUser->writeUC();
        } elseif (isset($beUser->uc['indexed_search']['action'])) {
            if ($request instanceof WebRequest) {
                $request->setControllerActionName($beUser->uc['indexed_search']['action']);
            }
            if (isset($beUser->uc['indexed_search']['arguments'])) {
                $request->setArguments($beUser->uc['indexed_search']['arguments']);
            }
        }

        parent::processRequest($request, $response);
    }

    /**
     * @param \TYPO3\CMS\IndexedSearch\Domain\Repository\AdministrationRepository $administrationRepository
     * @return void
     */
    public function injectAdministrationRepository(AdministrationRepository $administrationRepository)
    {
        $this->administrationRepository = $administrationRepository;
    }

    /**
     * Index action contains the most important statistics
     *
     * @return void
     */
    public function indexAction()
    {
        $this->view->assignMultiple([
            'records' => $this->administrationRepository->getRecordsNumbers(),
            'phash' => $this->administrationRepository->getPageHashTypes()
        ]);

        if ($this->pageUid) {
            $last24hours = ' AND tstamp > ' . ($GLOBALS['EXEC_TIME'] - 24 * 60 * 60);
            $last30days = ' AND tstamp > ' . ($GLOBALS['EXEC_TIME'] - 30 * 24 * 60 * 60);

            $this->view->assignMultiple([
                'pageUid' => $this->pageUid,
                'all' => $this->administrationRepository->getGeneralSearchStatistic('', $this->pageUid),
                'last24hours' => $this->administrationRepository->getGeneralSearchStatistic($last24hours, $this->pageUid),
                'last30days' => $this->administrationRepository->getGeneralSearchStatistic($last30days, $this->pageUid),
            ]);
        }
    }

    /**
     * Statistics for pages
     *
     * @return void
     */
    public function pagesAction()
    {
        $this->view->assign('records', $this->administrationRepository->getPageStatistic());
    }

    /**
     * Statistics for external documents
     *
     * @return void
     */
    public function externalDocumentsAction()
    {
        $this->view->assign('records', $this->administrationRepository->getExternalDocumentsStatistic());
    }

    /**
     * Statistics for a given page hash
     *
     * @param int $pageHash
     * @return void
     */
    public function statisticDetailsAction($pageHash = 0)
    {
        // Set back button
        $icon = $this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-view-go-up', Icon::SIZE_SMALL);
        $backButton = $this->view->getModuleTemplate()->getDocHeaderComponent()
            ->getButtonBar()->makeLinkButton()
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang.xml:administration.back'))
            ->setIcon($icon)
            ->setHref($this->getHref('Administration', 'statistic'));
        $this->view->getModuleTemplate()->getDocHeaderComponent()
            ->getButtonBar()->addButton($backButton);

        $pageHash = (int)$pageHash;
        $db = $this->getDatabaseConnection();
        $pageHashRow = $db->exec_SELECTgetSingleRow('*', 'index_phash', 'phash = ' . (int)$pageHash);

        if (!is_array($pageHashRow)) {
            $this->redirect('statistic');
        }

        $debugRow = $db->exec_SELECTgetRows('*', 'index_debug', 'phash = ' . (int)$pageHash);
        $debugInfo = [];
        $lexer = '';
        if (is_array($debugRow)) {
            $debugInfo = unserialize($debugRow[0]['debuginfo']);
            $lexer = $debugInfo['lexer'];
            unset($debugInfo['lexer']);
        }
        $pageRecord = BackendUtility::getRecord('pages', $pageHashRow['data_page_id']);
        $keywords = is_array($pageRecord) ? array_flip(GeneralUtility::trimExplode(',', $pageRecord['keywords'], true)) : [];
        $wordRecords = $db->exec_SELECTgetRows(
            'index_words.*, index_rel.*',
            'index_rel, index_words',
            'index_rel.phash = ' . (int)$pageHash . ' AND index_words.wid = index_rel.wid',
            '',
            'index_words.baseword'
        );
        foreach ($wordRecords as $id => $row) {
            if (isset($keywords[$row['baseword']])) {
                $wordRecords[$id]['is_keyword'] = true;
            }
        }
        $metaphoneRows = $metaphone = [];
        if ($this->enableMetaphoneSearch && is_array($wordRecords)) {
            // Group metaphone hash
            foreach ($wordRecords as $row) {
                $metaphoneRows[$row['metaphone']][] = $row['baseword'];
            }

            foreach ($metaphoneRows as $hash => $words) {
                if (count($words) > 1) {
                    $metaphone[] = [
                        'metaphone' => $this->indexer->metaphone($words[0], 1), $hash,
                        'words' => $words,
                        'hash' => $hash
                    ];
                }
            }
        }
        $this->view->assignMultiple([
            'phash' => $pageHash,
            'phashRow' => $pageHashRow,
            'words' => $wordRecords,
            'sections' => $db->exec_SELECTgetRows(
                '*',
                'index_section',
                'index_section.phash = ' . (int)$pageHash
            ),
            'topCount' => $db->exec_SELECTgetRows(
                'index_words.baseword, index_words.metaphone, index_rel.*',
                'index_rel, index_words',
                'index_rel.phash = ' . (int)$pageHash . ' AND index_words.wid = index_rel.wid
					 AND index_words.is_stopword=0',
                '',
                'index_rel.count DESC',
                 '20'
             ),
            'topFrequency' => $db->exec_SELECTgetRows(
                'index_words.baseword, index_words.metaphone, index_rel.*',
                'index_rel, index_words',
                'index_rel.phash = ' . (int)$pageHash . ' AND index_words.wid = index_rel.wid
					 AND index_words.is_stopword=0',
                '',
                'index_rel.freq DESC',
                '20'
            ),
            'debug' => $debugInfo,
            'lexer' => $lexer,
            'metaphone' => $metaphone,
            'page' => $pageRecord,
            'keywords' => $keywords
    ]);
    }

    /**
     * Save stop words and keywords
     *
     * @param string $pageHash
     * @param int $pageId
     * @param array $stopwords
     * @param array $keywords
     * @return void
     */
    public function saveStopwordsKeywordsAction($pageHash, $pageId, $stopwords = [], $keywords = [])
    {
        if ($this->getBackendUserAuthentication()->isAdmin()) {
            if (is_array($stopwords) && !empty($stopwords)) {
                $this->administrationRepository->saveStopWords($stopwords);
            }
            if (is_array($keywords) && !empty($keywords)) {
                $this->administrationRepository->saveKeywords($keywords, $pageId);
            }
        }

        $this->redirect('statisticDetails', null, null, ['pageHash' => $pageHash]);
    }

    /**
     * Statistics for a given word id
     *
     * @param int $id
     * @param int $pageHash
     * @return void
     */
    public function wordDetailAction($id = 0, $pageHash = 0)
    {
        $rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'index_phash.*, index_section.*, index_rel.*',
            'index_rel, index_section, index_phash',
            'index_rel.wid = ' . (int)$id . ' AND index_rel.phash = index_section.phash' . ' AND index_section.phash = index_phash.phash',
            '',
            'index_rel.freq DESC'
        );

        $this->view->assignMultiple([
            'rows' => $rows,
            'phash' => $pageHash
        ]);
    }

    /**
     * General statistics
     *
     * @param int $depth
     * @param string $mode
     * @return void
     */
    public function statisticAction($depth = 1, $mode = 'overview')
    {
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['external_parsers'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['external_parsers'] as $extension => $_objRef) {
                /** @var \TYPO3\CMS\IndexedSearch\FileContentParser $fileContentParser */
                $fileContentParser = GeneralUtility::getUserObj($_objRef);
                if ($fileContentParser->softInit($extension)) {
                    $this->external_parsers[$extension] = $fileContentParser;
                }
            }
        }
        $this->administrationRepository->external_parsers = $this->external_parsers;

        $allLines = $this->administrationRepository->getTree($this->pageUid, $depth, $mode);

        $this->view->assignMultiple([
            'levelTranslations' => explode('|', $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.enterSearchLevels')),
            'tree' => $allLines,
            'pageUid' => $this->pageUid,
            'mode' => $mode,
            'depth' => $depth
        ]);
    }

    /**
     * Remove item from index
     *
     * @param string $id
     * @param int $depth
     * @param string $mode
     * @return void
     */
    public function deleteIndexedItemAction($id, $depth = 1, $mode = 'overview')
    {
        $this->administrationRepository->removeIndexedPhashRow($id, $this->pageUid, $depth);
        $this->redirect('statistic', null, null, ['depth' => $depth, 'mode' => $mode]);
    }

    /**
     * Creates te URI for a backend action
     *
     * @param string $controller
     * @param string $action
     * @param array $parameters
     *
     * @return string
     */
    protected function getHref($controller, $action, $parameters = [])
    {
        $uriBuilder = $this->objectManager->get(UriBuilder::class);
        $uriBuilder->setRequest($this->request);
        return $uriBuilder->reset()->uriFor($action, $parameters, $controller);
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
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
