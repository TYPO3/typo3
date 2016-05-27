<?php
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
use TYPO3\CMS\Backend\Tree\View\ElementBrowserPageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface;

/**
 * Link handler for page (and content) links
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
        if (!$linkParts['url']) {
            return false;
        }

        $id = $linkParts['url'];
        $parts = explode('#', $id);
        if (count($parts) > 1) {
            $id = $parts[0];
            $anchor = $parts[1];
        } else {
            $anchor = '';
        }
        // Checking if the id-parameter is an alias.
        if (!MathUtility::canBeInterpretedAsInteger($id)) {
            $records = BackendUtility::getRecordsByField('pages', 'alias', $id);
            if (empty($records)) {
                return false;
            }
            $id = (int)$records[0]['uid'];
        }
        $pageRow = BackendUtility::getRecordWSOL('pages', $id);
        if (!$pageRow) {
            return false;
        }

        $this->linkParts = $linkParts;
        $this->linkParts['pageid'] = $id;
        $this->linkParts['anchor'] = $anchor;

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

        $id = $this->linkParts['pageid'];
        $pageRow = BackendUtility::getRecordWSOL('pages', $id);

        return htmlspecialchars($lang->getLL('page'))
            . ' \'' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($pageRow['title'], $titleLen)) . '\''
            . ' (ID:' . $id . ($this->linkParts['anchor'] ? ', #' . $this->linkParts['anchor'] : '') . ')';
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
        GeneralUtility::makeInstance(PageRenderer::class)->loadRequireJsModule('TYPO3/CMS/Recordlist/PageLinkHandler');

        $this->expandPage = isset($request->getQueryParams()['expandPage']) ? (int)$request->getQueryParams()['expandPage'] : 0;
        $this->setTemporaryDbMounts();

        $backendUser = $this->getBackendUser();

        /** @var ElementBrowserPageTreeView $pageTree */
        $pageTree = GeneralUtility::makeInstance(ElementBrowserPageTreeView::class);
        $pageTree->setLinkParameterProvider($this);
        $pageTree->ext_showPageId = (bool)$backendUser->getTSConfigVal('options.pageTree.showPageIdWithTitle');
        $pageTree->ext_showNavTitle = (bool)$backendUser->getTSConfigVal('options.pageTree.showNavTitle');
        $pageTree->addField('nav_title');

        $this->view->assign('temporaryTreeMountCancelLink', $this->getTemporaryTreeMountCancelNotice());
        $this->view->assign('tree', $pageTree->getBrowsableTree());
        $this->getRecordsOnExpandedPage($this->expandPage);
        return $this->view->render('Page');
    }

    /**
     * This adds all content elements on a page to the view and lets you create a link to the element.
     *
     * @param int $pageId Page uid to expand
     *
     * @return void
     */
    protected function getRecordsOnExpandedPage($pageId)
    {
        // If there is an anchor value (content element reference) in the element reference, then force an ID to expand:
        if (!$pageId && isset($this->linkParts['anchor'])) {
            // Set to the current link page id.
            $pageId = $this->linkParts['pageid'];
        }
        // Draw the record list IF there is a page id to expand:
        if ($pageId && MathUtility::canBeInterpretedAsInteger($pageId) && $this->getBackendUser()->isInWebMount($pageId)) {
            $pageId = (int)$pageId;

            $activePageRecord = BackendUtility::getRecordWSOL('pages', $pageId);
            $this->view->assign('expandActivePage', true);

            // Create header for listing, showing the page title/icon
            $this->view->assign('activePage', $activePageRecord);
            $this->view->assign('activePageTitle', BackendUtility::getRecordTitle('pages', $activePageRecord, true));
            $this->view->assign('activePageIcon', $this->iconFactory->getIconForRecord('pages', $activePageRecord, Icon::SIZE_SMALL)->render());

            // Look up tt_content elements from the expanded page
            $contentElements = $this->getDatabaseConnection()->exec_SELECTgetRows(
                'uid,header,hidden,starttime,endtime,fe_group,CType,colPos,bodytext',
                'tt_content',
                'pid=' . (int)$pageId . BackendUtility::deleteClause('tt_content')
                . BackendUtility::versioningPlaceholderClause('tt_content'),
                '',
                'colPos,sorting'
            );

            // Enrich list of records
            foreach ($contentElements as &$contentElement) {
                $contentElement['isSelected'] = !empty($this->linkParts) && (int)$this->linkParts['anchor'] === (int)$contentElement['uid'];
                $contentElement['icon'] = $this->iconFactory->getIconForRecord('tt_content', $contentElement, Icon::SIZE_SMALL)->render();
                $contentElement['title'] = BackendUtility::getRecordTitle('tt_content', $contentElement, true);
            }
            $this->view->assign('contentElements', $contentElements);
        }
    }

    /**
     * Check if a temporary tree mount is set and return a cancel button link
     *
     * @return string the link to cancel the temporary tree mount
     */
    protected function getTemporaryTreeMountCancelNotice()
    {
        if ((int)$this->getBackendUser()->getSessionData('pageTree_temporaryMountPoint') > 0) {
            return GeneralUtility::linkThisScript(['setTempDBmount' => 0]);
        } else {
            return '';
        }
    }

    /**
     * @return void
     */
    protected function setTemporaryDbMounts()
    {
        $backendUser = $this->getBackendUser();

        // Clear temporary DB mounts
        $tmpMount = GeneralUtility::_GET('setTempDBmount');
        if (isset($tmpMount)) {
            $backendUser->setAndSaveSessionData('pageTree_temporaryMountPoint', (int)$tmpMount);
        }
        // Set temporary DB mounts
        $alternativeWebmountPoint = (int)$backendUser->getSessionData('pageTree_temporaryMountPoint');
        if ($alternativeWebmountPoint) {
            $alternativeWebmountPoint = GeneralUtility::intExplode(',', $alternativeWebmountPoint);
            $backendUser->setWebmounts($alternativeWebmountPoint);
        } else {
            // Setting alternative browsing mounts (ONLY local to browse_links.php this script so they stay "read-only")
            $alternativeWebmountPoints = trim($backendUser->getTSConfigVal('options.pageTree.altElementBrowserMountPoints'));
            $appendAlternativeWebmountPoints = $backendUser->getTSConfigVal('options.pageTree.altElementBrowserMountPoints.append');
            if ($alternativeWebmountPoints) {
                $alternativeWebmountPoints = GeneralUtility::intExplode(',', $alternativeWebmountPoints);
                $this->getBackendUser()->setWebmounts($alternativeWebmountPoints, $appendAlternativeWebmountPoints);
            }
        }
    }

    /**
     * @return string[] Array of body-tag attributes
     */
    public function getBodyTagAttributes()
    {
        if (empty($this->linkParts)) {
            return [];
        }
        return [
            'data-current-link' => $this->linkParts['pageid'] . ($this->linkParts['anchor'] !== '' ? '#' . $this->linkParts['anchor'] : '')
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
            'expandPage' => isset($values['pid']) ? (int)$values['pid'] : $this->expandPage
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
        return !empty($this->linkParts) && (int)$this->linkParts['pageid'] === (int)$values['pid'];
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
        if (!empty($configuration['pageIdSelector.']['enabled'])) {
            array_push($this->linkAttributes, 'pageIdSelector');
            $fieldDefinitions['pageIdSelector'] = '
				<tr>
					<td>
						<label>
							' . htmlspecialchars($this->getLanguageService()->getLL('page_id')) . ':
						</label>
					</td>
					<td colspan="3">
						<input type="text" size="6" name="luid" id="luid" /> <input class="btn btn-default t3js-pageLink" type="submit" value="'
            . htmlspecialchars($this->getLanguageService()->getLL('setLink')) . '" />
					</td>
				</tr>';
        }
        return $fieldDefinitions;
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
