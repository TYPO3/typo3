<?php

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

namespace TYPO3\CMS\Recordlist\LinkHandler;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface;

/**
 * Link handler for page (and content) links
 * @internal This class is a specific LinkHandler implementation and is not part of the TYPO3's Core API.
 */
class PageLinkHandler extends AbstractLinkHandler implements LinkHandlerInterface, LinkParameterProviderInterface
{
    /**
     * @var int
     */
    protected $expandPage = 0;

    /**
     * Parts of the current link
     *
     * @var array
     */
    protected $linkParts = [];

    /**
     * Checks if this is the handler for the given link
     *
     * The handler may store this information locally for later usage.
     *
     * @param array $linkParts Link parts as returned from TypoLinkCodecService
     *
     * @return bool
     */
    public function canHandleLink(array $linkParts)
    {
        if (empty($linkParts['url'] ?? '')) {
            return false;
        }
        $data = $linkParts['url'];
        // Check if the page still exists
        if ((int)($data['pageuid'] ?? 0) > 0) {
            $pageRow = BackendUtility::getRecordWSOL('pages', $data['pageuid']);
            if (!$pageRow) {
                return false;
            }
        } elseif ($data['pageuid'] ?? '' !== 'current') {
            return false;
        }

        $this->linkParts = $linkParts;
        return true;
    }

    /**
     * Format the current link for HTML output
     *
     * @return string
     */
    public function formatCurrentUrl()
    {
        $lang = $this->getLanguageService();
        $titleLen = (int)$this->getBackendUser()->uc['titleLen'];

        $id = (int)$this->linkParts['url']['pageuid'];
        $pageRow = BackendUtility::getRecordWSOL('pages', $id);

        return $lang->getLL('page')
            . ' \'' . GeneralUtility::fixed_lgd_cs($pageRow['title'], $titleLen) . '\''
            . ' (ID: ' . $id . (!empty($this->linkParts['url']['fragment']) ? ', #' . $this->linkParts['url']['fragment'] : '') . ')';
    }

    /**
     * Render the link handler
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    public function render(ServerRequestInterface $request)
    {
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Recordlist/PageLinkHandler');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Viewport/ResizableNavigation');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Tree/PageBrowser');
        $this->getBackendUser()->initializeWebmountsForElementBrowser();

        $this->expandPage = isset($request->getQueryParams()['expandPage']) ? (int)$request->getQueryParams()['expandPage'] : 0;

        $this->view->assign('initialNavigationWidth', $this->getBackendUser()->uc['selector']['navigation']['width'] ?? 250);
        $this->view->assign('treeActions', ['link']);
        $this->getRecordsOnExpandedPage($this->expandPage);
        $this->view->setTemplate('Page');
        return '';
    }

    /**
     * This adds all content elements on a page to the view and lets you create a link to the element.
     *
     * @param int $pageId Page uid to expand
     */
    protected function getRecordsOnExpandedPage($pageId)
    {
        // If there is an anchor value (content element reference) in the element reference, then force an ID to expand:
        if (!$pageId && isset($this->linkParts['url']['fragment'])) {
            // Set to the current link page id.
            $pageId = $this->linkParts['url']['pageuid'];
        }
        $linkService = GeneralUtility::makeInstance(LinkService::class);
        $this->view->assign('expandedPage', $pageId ?: $this->linkParts['url']['pageuid'] ?? 0);
        // Draw the record list IF there is a page id to expand:
        if ($pageId && MathUtility::canBeInterpretedAsInteger($pageId) && $this->getBackendUser()->isInWebMount($pageId)) {
            $pageId = (int)$pageId;

            $activePageRecord = BackendUtility::getRecordWSOL('pages', $pageId);
            $this->view->assign('expandActivePage', true);

            // Create header for listing, showing the page title/icon
            $this->view->assign('activePage', $activePageRecord);
            $this->view->assign('activePageTitle', BackendUtility::getRecordTitle('pages', $activePageRecord, true));
            $this->view->assign('activePageIcon', $this->iconFactory->getIconForRecord('pages', $activePageRecord, Icon::SIZE_SMALL)->render());
            if ($this->isPageLinkable($activePageRecord)) {
                $this->view->assign('activePageLink', $linkService->asString(['type' => LinkService::TYPE_PAGE, 'pageuid' => $pageId]));
            }

            // Look up tt_content elements from the expanded page
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tt_content');

            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, (int)$this->getBackendUser()->workspace));

            $contentElements = $queryBuilder
                ->select('*')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq(
                            'pid',
                            $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->in(
                            'sys_language_uid',
                            $queryBuilder->createNamedParameter([$activePageRecord['sys_language_uid'], -1], Connection::PARAM_INT_ARRAY)
                        )
                    )
                )
                ->orderBy('colPos')
                ->addOrderBy('sorting')
                ->execute()
                ->fetchAllAssociative();

            // Enrich list of records
            foreach ($contentElements as &$contentElement) {
                BackendUtility::workspaceOL('tt_content', $contentElement);
                $contentElement['url'] = $linkService->asString(['type' => LinkService::TYPE_PAGE, 'pageuid' => $pageId, 'fragment' => $contentElement['uid']]);
                $contentElement['isSelected'] = !empty($this->linkParts) && (int)$this->linkParts['url']['fragment'] === (int)$contentElement['uid'];
                $contentElement['icon'] = $this->iconFactory->getIconForRecord('tt_content', $contentElement, Icon::SIZE_SMALL)->render();
                $contentElement['title'] = BackendUtility::getRecordTitle('tt_content', $contentElement, true);
            }
            $this->view->assign('contentElements', $contentElements);
        }
    }

    /**
     * @return string[] Array of body-tag attributes
     */
    public function getBodyTagAttributes()
    {
        if (count($this->linkParts) === 0 || empty($this->linkParts['url']['pageuid'])) {
            return [];
        }
        return [
            'data-current-link' => GeneralUtility::makeInstance(LinkService::class)->asString([
                'type' => LinkService::TYPE_PAGE,
                'pageuid' => (int)$this->linkParts['url']['pageuid'],
                'fragment' => $this->linkParts['url']['fragment'] ?? '',
            ]),
        ];
    }

    /**
     * @param array $values Array of values to include into the parameters or which might influence the parameters
     *
     * @return string[] Array of parameters which have to be added to URLs
     */
    public function getUrlParameters(array $values)
    {
        $parameters = [
            'expandPage' => isset($values['pid']) ? (int)$values['pid'] : $this->expandPage,
        ];
        return array_merge($this->linkBrowser->getUrlParameters($values), $parameters);
    }

    /**
     * @param array $values Values to be checked
     *
     * @return bool Returns TRUE if the given values match the currently selected item
     */
    public function isCurrentlySelectedItem(array $values)
    {
        return !empty($this->linkParts) && (int)$this->linkParts['url']['pageuid'] === (int)$values['pid'];
    }

    /**
     * Returns the URL of the current script
     *
     * @return string
     */
    public function getScriptUrl()
    {
        return $this->linkBrowser->getScriptUrl();
    }

    /**
     * @param string[] $fieldDefinitions Array of link attribute field definitions
     * @return string[]
     */
    public function modifyLinkAttributes(array $fieldDefinitions)
    {
        $configuration = $this->linkBrowser->getConfiguration();
        // Depending where the configuration is set it can be 'pageIdSelector' (CKEditor yaml) or 'pageIdSelector.' (TSconfig)
        if (!empty($configuration['pageIdSelector']['enabled']) || !empty($configuration['pageIdSelector.']['enabled'])) {
            $this->linkAttributes[] = 'pageIdSelector';
            $fieldDefinitions['pageIdSelector'] = '
				<form class="form-horizontal"><div class="form-group form-group-sm">
					<label class="col-4 control-label">
						' . htmlspecialchars($this->getLanguageService()->getLL('page_id')) . '
						</label>
					<div class="col-2">
						<input type="number" size="6" name="luid" id="luid" class="form-control" />
					</div>
					<div class="col-6">
						<input class="btn btn-default t3js-pageLink" type="submit" value="' . htmlspecialchars($this->getLanguageService()->getLL('setLink')) . '" />
					</div>
				</div></form>';
        }
        return $fieldDefinitions;
    }

    protected function isPageLinkable(array $page): bool
    {
        return !in_array((int)$page['doktype'], [PageRepository::DOKTYPE_RECYCLER, PageRepository::DOKTYPE_SYSFOLDER, PageRepository::DOKTYPE_SPACER]);
    }
}
