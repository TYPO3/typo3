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

namespace TYPO3\CMS\Backend\LinkHandler;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\AbstractLinkBrowserController;
use TYPO3\CMS\Backend\RecordList\ElementBrowserRecordList;
use TYPO3\CMS\Backend\Tree\View\LinkParameterProviderInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\RecordSearchBoxComponent;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * This link handler allows linking to arbitrary database records.
 * They can be configured in addition to default core link handlers and are rendered
 * as additional tab in the link browser.
 *
 * A typical use case is linking a single new record.
 *
 * Additional page TSconfig TCEMAIN.linkHandler setup is necessary to use this.
 *
 * A typical configuration looks like the below snippet. It configures a tab that allows linking to
 * ext:news news records ("table" is mandatory), labels them as "Book reports" (LLL: is possible),
 * forces a specific page-uid (optional), and hides page-tree selection (optional).
 *
 * TCEMAIN.linkHandler.bookreports {
 *   handler = TYPO3\CMS\Backend\LinkHandler\RecordLinkHandler
 *   label = Book Reports
 *   configuration {
 *     table = tx_news_domain_model_news
 *     storagePid = 42
 *     hidePageTree = 1
 *   }
 * }
 *
 * @internal This class is a specific LinkHandler implementation and is not part of the TYPO3's Core API.
 */
final class RecordLinkHandler extends AbstractLinkHandler implements LinkHandlerInterface, LinkParameterProviderInterface
{
    /**
     * Configuration key in TSconfig TCEMAIN.linkHandler.<identifier>
     */
    protected string $identifier;

    /**
     * Specific TSconfig for the current instance (corresponds to TCEMAIN.linkHandler.record.<identifier>.configuration)
     */
    protected array $configuration = [];

    /**
     * Parts of the current link
     */
    protected array $linkParts = [];

    protected int $expandPage = 0;

    public function __construct(
        private readonly ElementBrowserRecordList $elementBrowserRecordList,
        private readonly RecordSearchBoxComponent $recordSearchBoxComponent,
        private readonly LinkService $linkService,
    ) {
        parent::__construct();
    }

    public function initialize(AbstractLinkBrowserController $linkBrowser, $identifier, array $configuration)
    {
        parent::initialize($linkBrowser, $identifier, $configuration);
        $this->identifier = $identifier;
        if (empty($configuration['table'])) {
            throw new \LogicException(
                'Page TSconfig TCEMAIN.linkHandler.' . $identifier . '.configuration.table is mandatory and must be set to a table name.',
                1657960610
            );
        }
        $this->configuration = $configuration;
    }

    /**
     * Checks if this is the right handler for the given link.
     * Also stores information locally about currently linked record.
     *
     * @param array $linkParts Link parts as returned from TypoLinkCodecService
     */
    public function canHandleLink(array $linkParts): bool
    {
        if (!$linkParts['url'] || !isset($linkParts['url']['identifier']) || $linkParts['url']['identifier'] !== $this->identifier) {
            return false;
        }

        $data = $linkParts['url'];

        // Get the related record
        $table = $this->configuration['table'];
        $record = BackendUtility::getRecord($table, $data['uid']);
        if ($record === null) {
            $linkParts['title'] = $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:recordNotFound');
        } else {
            $linkParts['pid'] = (int)$record['pid'];
            $linkParts['title'] = !empty($linkParts['title']) ? $linkParts['title'] : BackendUtility::getRecordTitle($table, $record);
        }
        $linkParts['tableName'] = $this->getLanguageService()->sL($GLOBALS['TCA'][$table]['ctrl']['title']);
        $linkParts['url']['type'] = $linkParts['type'];
        $this->linkParts = $linkParts;

        return true;
    }

    /**
     * Formats information for the current record for HTML output.
     */
    public function formatCurrentUrl(): string
    {
        return sprintf(
            '%s: %s [uid: %d]',
            $this->linkParts['tableName'],
            $this->linkParts['title'],
            $this->linkParts['url']['uid']
        );
    }

    /**
     * Renders the link handler.
     */
    public function render(ServerRequestInterface $request): string
    {
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/record-link-handler.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/record-search.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/viewport/resizable-navigation.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/column-selector-button.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/tree/page-browser.js');
        $this->getBackendUser()->initializeWebmountsForElementBrowser();

        // Define the current page
        if (isset($request->getQueryParams()['expandPage'])) {
            $this->expandPage = (int)$request->getQueryParams()['expandPage'];
        } elseif (isset($this->configuration['storagePid'])) {
            $this->expandPage = (int)$this->configuration['storagePid'];
        } elseif (isset($this->linkParts['pid'])) {
            $this->expandPage = (int)$this->linkParts['pid'];
        }

        $pageTreeMountPoints = (string)($this->configuration['pageTreeMountPoints'] ?? '');
        $this->view->assignMultiple([
            'treeEnabled' => (bool)($this->configuration['hidePageTree'] ?? false) === false,
            'pageTreeMountPoints' => GeneralUtility::intExplode(',', $pageTreeMountPoints, true),
            'recordList' => $this->renderTableRecords($request),
            'initialNavigationWidth' => $this->getBackendUser()->uc['selector']['navigation']['width'] ?? 250,
            'treeActions' => ['link'],
        ]);

        return $this->view->render('LinkBrowser/Record');
    }

    /**
     * Returns attributes for the body tag.
     *
     * @return string[] Array of body-tag attributes
     */
    public function getBodyTagAttributes(): array
    {
        $attributes = [
            'data-linkbrowser-identifier' => 't3://record?identifier=' . $this->identifier . '&uid=',
        ];
        if (!empty($this->linkParts)) {
            $attributes['data-linkbrowser-current-link'] = $this->linkService->asString($this->linkParts['url']);
        }
        return $attributes;
    }

    /**
     * Returns all parameters needed to build a URL with all the necessary information.
     *
     * @param array $values Array of values to include into the parameters or which might influence the parameters
     * @return string[] Array of parameters which have to be added to URLs
     */
    public function getUrlParameters(array $values): array
    {
        $pid = isset($values['pid']) ? (int)$values['pid'] : $this->expandPage;
        $parameters = [
            'expandPage' => $pid,
        ];

        return array_merge(
            $this->linkBrowser->getUrlParameters($values),
            ['P' => $this->linkBrowser->getParameters()],
            $parameters
        );
    }

    /**
     * Checks if the submitted page matches the current page.
     *
     * @param array $values Values to be checked
     * @return bool Returns TRUE if the given values match the currently selected item
     */
    public function isCurrentlySelectedItem(array $values): bool
    {
        return !empty($this->linkParts) && (int)$this->linkParts['pid'] === (int)$values['pid'];
    }

    /**
     * Returns the URL of the current script
     */
    public function getScriptUrl(): string
    {
        return $this->linkBrowser->getScriptUrl();
    }

    /**
     * Render elements of configured table
     */
    protected function renderTableRecords(ServerRequestInterface $request): string
    {
        $html = [];
        $backendUser = $this->getBackendUser();
        $selectedPage = $this->expandPage;
        if ($selectedPage < 0 || !$backendUser->isInWebMount($selectedPage)) {
            return '';
        }
        $table = $this->configuration['table'];
        $permsClause = $backendUser->getPagePermsClause(Permission::PAGE_SHOW);
        $pageInfo = BackendUtility::readPageAccess($selectedPage, $permsClause);
        $selectedTable = (string)($request->getParsedBody()['table'] ?? $request->getQueryParams()['table'] ?? '');
        $searchWord = (string)($request->getParsedBody()['searchTerm'] ?? $request->getQueryParams()['searchTerm'] ?? '');
        $pointer = (int)($request->getParsedBody()['pointer'] ?? $request->getQueryParams()['pointer'] ?? 0);

        // If table is 'pages', add a pre-entry to make selected page selectable directly.
        $titleLen = (int)$backendUser->uc['titleLen'];
        $mainPageRecord = BackendUtility::getRecordWSOL('pages', $selectedPage);
        if (is_array($mainPageRecord)) {
            $pText = htmlspecialchars(GeneralUtility::fixed_lgd_cs($mainPageRecord['title'], $titleLen));
            $html[] = '<p>' . $this->iconFactory->getIconForRecord('pages', $mainPageRecord, Icon::SIZE_SMALL)->render() . '&nbsp;';
            if ($table === 'pages') {
                $html[] = '<span data-uid="' . htmlspecialchars((string)$mainPageRecord['uid']) . '" data-table="pages" data-title="' . htmlspecialchars($mainPageRecord['title']) . '">';
                $html[] =    '<a href="#" data-close="0">' . $this->iconFactory->getIcon('actions-plus', Icon::SIZE_SMALL)->render() . '</a>';
                $html[] =    '<a href="#" data-close="1">' . $pText . '</a>';
                $html[] = '</span>';
            } else {
                $html[] = $pText;
            }
            $html[] = '</p>';
        }

        $dbList = $this->elementBrowserRecordList;
        $dbList->setRequest($request);
        $dbList->setOverrideUrlParameters(array_merge($this->getUrlParameters([]), ['mode' => 'db', 'expandPage' => $selectedPage]), $request);
        $dbList->setIsEditable(false);
        $dbList->calcPerms = new Permission($backendUser->calcPerms($pageInfo));
        $dbList->noControlPanels = true;
        $dbList->clickMenuEnabled = false;
        $dbList->displayRecordDownload = false;
        $dbList->tableList = $table;
        $dbList->start($selectedPage, $selectedTable, MathUtility::forceIntegerInRange($pointer, 0, 100000), $searchWord);

        $html[] = $this->recordSearchBoxComponent
            ->setSearchWord($searchWord)
            ->render($request, $dbList->listURL('', '-1', 'pointer,searchTerm'));
        $html[] = $dbList->generateList();

        return implode("\n", $html);
    }
}
