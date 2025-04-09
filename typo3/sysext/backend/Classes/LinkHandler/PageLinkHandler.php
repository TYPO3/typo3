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
use TYPO3\CMS\Backend\Tree\View\LinkParameterProviderInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Link to a page record.
 *
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

        $idInfo = 'ID: ' . $id . (!empty($this->linkParts['url']['fragment']) ? ', #' . $this->linkParts['url']['fragment'] : '');

        $permsClause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
        $pageRecord = BackendUtility::readPageAccess($id, $permsClause);
        if ($pageRecord === false) {
            return $lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:page') . ' ' . $idInfo;
        }

        $pageTitle = $pageRecord['title'] ?? '';
        return $lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:page')
            . ($pageTitle ? ' \'' . GeneralUtility::fixed_lgd_cs($pageTitle, $titleLen) . '\'' : '')
            . ' (' . $idInfo . ')';
    }

    /**
     * Render the link handler
     */
    public function render(ServerRequestInterface $request): string
    {
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/page-link-handler.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/viewport/resizable-navigation.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/tree/page-browser.js');
        $this->getBackendUser()->initializeWebmountsForElementBrowser();

        $this->expandPage = isset($request->getQueryParams()['expandPage']) ? (int)$request->getQueryParams()['expandPage'] : 0;

        $this->view->assign('initialNavigationWidth', $this->getBackendUser()->uc['selector']['navigation']['width'] ?? 250);
        $this->view->assign('treeActions', ['link']);
        $this->getRecordsOnExpandedPage($this->expandPage);
        return $this->view->render('LinkBrowser/Page');
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
            $this->view->assign('activePageIcon', $this->iconFactory->getIconForRecord('pages', $activePageRecord, IconSize::SMALL)->render());
            if ($this->isPageLinkable($activePageRecord)) {
                $this->view->assign('activePageLink', $linkService->asString(['type' => LinkService::TYPE_PAGE, 'pageuid' => $pageId]));
            }

            // Look up tt_content elements from the expanded page
            // @todo: this should be grouped by colPos and use the layout from the page module
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tt_content');

            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));

            $contentElements = $queryBuilder
                ->select('*')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq(
                            'pid',
                            $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)
                        ),
                        $queryBuilder->expr()->in(
                            'sys_language_uid',
                            $queryBuilder->createNamedParameter([$activePageRecord['sys_language_uid'], -1], Connection::PARAM_INT_ARRAY)
                        )
                    )
                )
                ->orderBy('colPos')
                ->addOrderBy('sorting')
                ->executeQuery()
                ->fetchAllAssociative();

            // Enrich list of records
            $items = [];
            foreach ($contentElements as $contentElement) {
                BackendUtility::workspaceOL('tt_content', $contentElement, $this->getBackendUser()->workspace, true);
                if (is_array($contentElement)) {
                    // Ensure to always link to the live version of the record
                    if ((int)$contentElement['t3ver_oid'] > 0) {
                        $contentElementId = (int)$contentElement['t3ver_oid'];
                    } else {
                        $contentElementId = (int)$contentElement['uid'];
                    }
                    $contentElement['url'] = $linkService->asString(['type' => LinkService::TYPE_PAGE, 'pageuid' => $pageId, 'fragment' => $contentElementId]);
                    $contentElement['isSelected'] = (int)($this->linkParts['url']['fragment'] ?? 0) === $contentElementId;
                    $contentElement['icon'] = $this->iconFactory->getIconForRecord('tt_content', $contentElement, IconSize::SMALL)->render();
                    $contentElement['title'] = BackendUtility::getRecordTitle('tt_content', $contentElement, true);
                    $items[] = $contentElement;
                }
            }
            $this->view->assign('contentElements', $items);
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
            'data-linkbrowser-current-link' => GeneralUtility::makeInstance(LinkService::class)->asString([
                'type' => LinkService::TYPE_PAGE,
                'pageuid' => (int)$this->linkParts['url']['pageuid'],
                'fragment' => $this->linkParts['url']['fragment'] ?? '',
            ]),
        ];
    }

    /**
     * @param array $values Array of values to include into the parameters or which might influence the parameters
     * @return string[] Array of parameters which have to be added to URLs
     */
    public function getUrlParameters(array $values): array
    {
        $parameters = [
            'expandPage' => isset($values['pid']) ? (int)$values['pid'] : $this->expandPage,
        ];
        return array_merge($this->linkBrowser->getUrlParameters($values), $parameters);
    }

    /**
     * @param string[] $fieldDefinitions Array of link attribute field definitions
     * @return string[]
     */
    public function modifyLinkAttributes(array $fieldDefinitions)
    {
        $configuration = $this->linkBrowser->getConfiguration();
        // Depending on where the configuration is set it can be 'pageIdSelector' (CKEditor yaml) or 'pageIdSelector.' (TSconfig)
        if (!empty($configuration['pageIdSelector']['enabled']) || !empty($configuration['pageIdSelector.']['enabled'])) {
            $this->linkAttributes[] = 'pageIdSelector';
            $fieldDefinitions['pageIdSelector'] = '
				<form><div class="row mt-3">
					<label class="col-3 col-form-label">
						' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:page_id')) . '
						</label>
					<div class="col-2">
						<input type="number" size="6" name="luid" id="luid" class="form-control" />
					</div>
					<div class="col-7">
						<input class="btn btn-default t3js-pageLink" type="submit" value="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:setLink')) . '" />
					</div>
				</div></form>';
        }
        return $fieldDefinitions;
    }

    protected function isPageLinkable(array $page): bool
    {
        return !in_array((int)$page['doktype'], [PageRepository::DOKTYPE_SYSFOLDER, PageRepository::DOKTYPE_SPACER]);
    }
}
