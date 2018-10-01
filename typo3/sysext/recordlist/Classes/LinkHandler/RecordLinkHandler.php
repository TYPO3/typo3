<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Recordlist\LinkHandler;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Recordlist\Browser\RecordBrowser;
use TYPO3\CMS\Recordlist\Controller\AbstractLinkBrowserController;
use TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface;
use TYPO3\CMS\Recordlist\Tree\View\RecordBrowserPageTreeView;

/**
 * Link handler for arbitrary database records
 * @internal This class is a specific LinkHandler implementation and is not part of the TYPO3's Core API.
 */
class RecordLinkHandler extends AbstractLinkHandler implements LinkHandlerInterface, LinkParameterProviderInterface
{
    /**
     * Configuration key in TSconfig TCEMAIN.linkHandler.record
     *
     * @var string
     */
    protected $identifier;

    /**
     * Specific TSconfig for the current instance (corresponds to TCEMAIN.linkHandler.record.identifier.configuration)
     *
     * @var array
     */
    protected $configuration = [];

    /**
     * Parts of the current link
     *
     * @var array
     */
    protected $linkParts = [];

    /**
     * @var int
     */
    protected $expandPage = 0;

    /**
     * Initializes the handler.
     *
     * @param AbstractLinkBrowserController $linkBrowser
     * @param string $identifier
     * @param array $configuration Page TSconfig
     */
    public function initialize(AbstractLinkBrowserController $linkBrowser, $identifier, array $configuration)
    {
        parent::initialize($linkBrowser, $identifier, $configuration);
        $this->identifier = $identifier;
        $this->configuration = $configuration;
    }

    /**
     * Checks if this is the right handler for the given link.
     *
     * Also stores information locally about currently linked record.
     *
     * @param array $linkParts Link parts as returned from TypoLinkCodecService
     * @return bool
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
            $linkParts['title'] = $this->getLanguageService()->getLL('recordNotFound');
        } else {
            $linkParts['tableName'] = $this->getLanguageService()->sL($GLOBALS['TCA'][$table]['ctrl']['title']);
            $linkParts['pid'] = (int)$record['pid'];
            $linkParts['title'] = $linkParts['title'] ?: BackendUtility::getRecordTitle($table, $record);
        }
        $linkParts['url']['type'] = $linkParts['type'];
        $this->linkParts = $linkParts;

        return true;
    }

    /**
     * Formats information for the current record for HTML output.
     *
     * @return string
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
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    public function render(ServerRequestInterface $request): string
    {
        // Declare JS module
        GeneralUtility::makeInstance(PageRenderer::class)->loadRequireJsModule('TYPO3/CMS/Recordlist/RecordLinkHandler');

        // Define the current page
        if (isset($request->getQueryParams()['expandPage'])) {
            $this->expandPage = (int)$request->getQueryParams()['expandPage'];
        } elseif (isset($this->configuration['storagePid'])) {
            $this->expandPage = (int)$this->configuration['storagePid'];
        } elseif (isset($this->linkParts['pid'])) {
            $this->expandPage = (int)$this->linkParts['pid'];
        }
        $this->setTemporaryDbMounts();

        $databaseBrowser = GeneralUtility::makeInstance(RecordBrowser::class);

        $recordList = $databaseBrowser->displayRecordsForPage(
            $this->expandPage,
            $this->configuration['table'],
            $this->getUrlParameters([])
        );

        $path = GeneralUtility::getFileAbsFileName('EXT:recordlist/Resources/Private/Templates/LinkBrowser/Record.html');
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename($path);
        $view->assignMultiple([
            'tree' => $this->configuration['hidePageTree'] ? '' : $this->renderPageTree(),
            'recordList' => $recordList,
        ]);

        return $view->render();
    }

    /**
     * Renders the page tree.
     *
     * @return string
     */
    protected function renderPageTree(): string
    {
        $userTsConfig = $this->getBackendUser()->getTSConfig();

        /** @var RecordBrowserPageTreeView $pageTree */
        $pageTree = GeneralUtility::makeInstance(RecordBrowserPageTreeView::class);
        $pageTree->setLinkParameterProvider($this);
        $pageTree->ext_showPageId = (bool)($userTsConfig['options.']['pageTree.']['showPageIdWithTitle'] ?? false);
        $pageTree->ext_showNavTitle = (bool)($userTsConfig['options.']['pageTree.']['showNavTitle'] ?? false);
        $pageTree->ext_showPathAboveMounts = (bool)($userTsConfig['options.']['pageTree.']['showPathAboveMounts'] ?? false);
        $pageTree->addField('nav_title');

        // Load the mount points, if any
        // NOTE: mount points actually override the page tree
        if (!empty($this->configuration['pageTreeMountPoints'])) {
            $pageTree->MOUNTS = GeneralUtility::intExplode(',', $this->configuration['pageTreeMountPoints'], true);
        }

        return $pageTree->getBrowsableTree();
    }

    /**
     * Returns attributes for the body tag.
     *
     * @return string[] Array of body-tag attributes
     */
    public function getBodyTagAttributes(): array
    {
        $attributes = [
            'data-identifier' => 't3://record?identifier=' . $this->identifier . '&uid=',
        ];
        if (!empty($this->linkParts)) {
            $attributes['data-current-link'] = GeneralUtility::makeInstance(LinkService::class)->asString($this->linkParts['url']);
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
     *
     * @return string
     */
    public function getScriptUrl(): string
    {
        return $this->linkBrowser->getScriptUrl();
    }
}
