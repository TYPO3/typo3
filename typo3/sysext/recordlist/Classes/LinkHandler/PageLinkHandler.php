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

        return $lang->getLL('page', true)
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
        $pageTree->ext_showNavTitle = (bool)$backendUser->getTSConfigVal('options.pageTree.showNavTitle');
        $pageTree->ext_showPageId = (bool)$backendUser->getTSConfigVal('options.pageTree.showPageIdWithTitle');
        $pageTree->ext_showPathAboveMounts = (bool)$backendUser->getTSConfigVal('options.pageTree.showPathAboveMounts');
        $pageTree->addField('nav_title');
        $tree = $pageTree->getBrowsableTree();

        return '

				<!--
					Wrapper table for page tree / record list:
				-->
				<table border="0" cellpadding="0" cellspacing="0" id="typo3-linkPages">
					<tr>
						<td class="c-wCell" valign="top"><h3>' . $this->getLanguageService()->getLL('pageTree') . ':</h3>'
                            . $this->getTemporaryTreeMountCancelNotice() . $tree . '</td>
						<td class="c-wCell" valign="top">' . $this->expandPage($this->expandPage) . '</td>
					</tr>
				</table>';
    }

    /**
     * This displays all content elements on a page and lets you create a link to the element.
     *
     * @param int $expPageId Page uid to expand
     *
     * @return string HTML output. Returns content only if the ->expandPage value is set (pointing to a page uid to show tt_content records from ...)
     */
    public function expandPage($expPageId)
    {
        // If there is an anchor value (content element reference) in the element reference, then force an ID to expand:
        if (!$expPageId && isset($this->linkParts['anchor'])) {
            // Set to the current link page id.
            $expPageId = $this->linkParts['pageid'];
        }
        // Draw the record list IF there is a page id to expand:
        if (!$expPageId || !MathUtility::canBeInterpretedAsInteger($expPageId) || !$this->getBackendUser()->isInWebMount($expPageId)) {
            return '';
        }

        // Set header:
        $out = '<h3>' . $this->getLanguageService()->getLL('contentElements') . ':</h3>';
        // Create header for listing, showing the page title/icon:
        $mainPageRec = BackendUtility::getRecordWSOL('pages', $expPageId);
        $db = $this->getDatabaseConnection();
        $out .= '
			<ul class="list-tree list-tree-root list-tree-root-clean">
				<li class="list-tree-control-open">
					<span class="list-tree-group">
						<span class="list-tree-icon">' . $this->iconFactory->getIconForRecord('pages', $mainPageRec, Icon::SIZE_SMALL)->render() . '</span>
						<span class="list-tree-title">' . htmlspecialchars(BackendUtility::getRecordTitle('pages', $mainPageRec, true)) . '</span>
					</span>
					<ul>
			';

        // Look up tt_content elements from the expanded page:
        $res = $db->exec_SELECTquery(
            '*',
            'tt_content',
            'pid=' . (int)$expPageId . BackendUtility::deleteClause('tt_content')
            . BackendUtility::versioningPlaceholderClause('tt_content'),
            '',
            'colPos,sorting'
        );
        // Traverse list of records:
        $c = 0;
        while ($row = $db->sql_fetch_assoc($res)) {
            $c++;
            $icon = $this->iconFactory->getIconForRecord('tt_content', $row, Icon::SIZE_SMALL)->render();
            $selected = '';
            if (!empty($this->linkParts) && (int)$this->linkParts['anchor'] === (int)$row['uid']) {
                $selected = ' class="active"';
            }
            // Putting list element HTML together:
            // Output of BackendUtility::getRecordTitle() is already hsc'ed
            $out .= '
				<li' . $selected . '>
					<span class="list-tree-group">
						<span class="list-tree-icon">
							' . $icon . '
						</span>
						<span class="list-tree-title">
							<a href="#" class="t3js-pageLink" data-id="' . (int)$expPageId . '" data-anchor="#' . (int)$row['uid'] . '">
								' . BackendUtility::getRecordTitle('tt_content', $row, true) . '
							</a>
						</span>
					</span>
				</li>
				';
        }
        $out .= '
					</ul>
				</li>
			</ul>
			';

        return $out;
    }

    /**
     * Check if a temporary tree mount is set and return a cancel button
     *
     * @return string HTML code
     */
    protected function getTemporaryTreeMountCancelNotice()
    {
        if ((int)$this->getBackendUser()->getSessionData('pageTree_temporaryMountPoint') === 0) {
            return '';
        }
        $link = '<p><a href="' . htmlspecialchars(GeneralUtility::linkThisScript(['setTempDBmount' => 0])) . '" class="btn btn-primary">'
            . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.temporaryDBmount', true) . '</a></p>';
        return $link;
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
							' . $this->getLanguageService()->getLL('page_id', true) . ':
						</label>
					</td>
					<td colspan="3">
						<input type="text" size="6" name="luid" id="luid" /> <input class="btn btn-default t3js-pageLink" type="submit" value="'
            . $this->getLanguageService()->getLL('setLink', true) . '" />
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
