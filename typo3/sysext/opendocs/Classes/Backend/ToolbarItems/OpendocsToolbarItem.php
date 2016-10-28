<?php
namespace TYPO3\CMS\Opendocs\Backend\ToolbarItems;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Alist of all open documents
 */
class OpendocsToolbarItem implements ToolbarItemInterface
{
    /**
     * @var array
     */
    protected $openDocs;

    /**
     * @var array
     */
    protected $recentDocs;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->getLanguageService()->includeLLFile('EXT:opendocs/Resources/Private/Language/locallang.xlf');
        $this->loadDocsFromUserSession();
        $pageRenderer = $this->getPageRenderer();
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Opendocs/Toolbar/OpendocsMenu');
    }

    /**
     * Checks whether the user has access to this toolbar item
     *
     * @return bool TRUE if user has access, FALSE if not
     */
    public function checkAccess()
    {
        $conf = $this->getBackendUser()->getTSConfig('backendToolbarItem.tx_opendocs.disabled');
        return $conf['value'] != 1;
    }

    /**
     * Loads the opened and recently opened documents from the user
     *
     * @return void
     */
    public function loadDocsFromUserSession()
    {
        $backendUser = $this->getBackendUser();
        list($this->openDocs, ) = $backendUser->getModuleData('FormEngine', 'ses');
        $this->recentDocs = $backendUser->getModuleData('opendocs::recent');
    }

    /**
     * Render toolbar icon
     *
     * @return string HTML
     */
    public function getItem()
    {
        $numDocs = count($this->openDocs);
        $title = $this->getLanguageService()->getLL('toolbaritem', true);

        $opendocsMenu = [];
        $opendocsMenu[] = '<span title="' . $title . '">' . $this->iconFactory->getIcon('apps-toolbar-menu-opendocs', Icon::SIZE_SMALL)->render('inline') . '</span>';
        $opendocsMenu[] = '<span class="badge" id="tx-opendocs-counter">' . $numDocs . '</span>';

        return implode(LF, $opendocsMenu);
    }

    /**
     * Render drop down
     *
     * @return string HTML
     */
    public function getDropDown()
    {
        $languageService = $this->getLanguageService();
        $openDocuments = $this->openDocs;
        $recentDocuments = $this->recentDocs;
        $entries = [];
        if (!empty($openDocuments)) {
            $entries[] = '<li class="dropdown-header">' . $languageService->getLL('open_docs', true) . '</li>';
            $i = 0;
            foreach ($openDocuments as $md5sum => $openDocument) {
                $i++;
                $entries[] = $this->renderMenuEntry($openDocument, $md5sum, false, $i == 1);
            }
            $entries[] = '<li class="divider"></li>';
        }
        // If there are "recent documents" in the list, add them
        if (!empty($recentDocuments)) {
            $entries[] = '<li class="dropdown-header">' . $languageService->getLL('recent_docs', true) . '</li>';
            $i = 0;
            foreach ($recentDocuments as $md5sum => $recentDocument) {
                $i++;
                $entries[] = $this->renderMenuEntry($recentDocument, $md5sum, true, $i == 1);
            }
        }
        if (!empty($entries)) {
            $content = '<ul class="dropdown-list">' . implode('', $entries) . '</ul>';
        } else {
            $content = '<p>' . $languageService->getLL('no_docs', true) . '</p>';
        }
        return $content;
    }

    /**
     * Returns the recent documents list as an array
     *
     * @param array $document
     * @param string $md5sum
     * @param bool $isRecentDoc
     * @param bool $isFirstDoc
     * @return array All recent documents as list-items
     */
    protected function renderMenuEntry($document, $md5sum, $isRecentDoc = false, $isFirstDoc = false)
    {
        $table = $document[3]['table'];
        $uid = $document[3]['uid'];
        $record = BackendUtility::getRecordWSOL($table, $uid);
        if (!is_array($record)) {
            // Record seems to be deleted
            return '';
        }
        $label = htmlspecialchars(strip_tags(htmlspecialchars_decode($document[0])));
        $icon = $this->iconFactory->getIconForRecord($table, $record, Icon::SIZE_SMALL)->render();
        $link = BackendUtility::getModuleUrl('record_edit')
            . '&' . $document[2]
            . '&returnUrl=' . rawurlencode(BackendUtility::getModuleUrl('web_list') . '&id=' . (int)$document[3]['pid']);
        $pageId = (int)$document[3]['uid'];
        if ($document[3]['table'] !== 'pages') {
            $pageId = (int)$document[3]['pid'];
        }
        $onClickCode = 'jump(' . GeneralUtility::quoteJSvalue($link) . ', \'web_list\', \'web\', ' . $pageId . '); TYPO3.OpendocsMenu.toggleMenu(); return false;';
        if (!$isRecentDoc) {
            $title = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:rm.closeDoc', true);
            // Open document
            $closeIcon = $this->iconFactory->getIcon('actions-close', Icon::SIZE_SMALL)->render('inline');
            $entry = '
				<li class="opendoc">
					<a href="#" class="dropdown-list-link dropdown-link-list-add-close" onclick="' . htmlspecialchars($onClickCode) . '" target="content">' . $icon . ' ' . $label . '</a>
					<a href="#" class="dropdown-list-link-close" data-opendocsidentifier="' . $md5sum . '" title="' . $title . '">' . $closeIcon . '</a>
				</li>';
        } else {
            // Recently used document
            $entry = '
				<li>
					<a href="#" class="dropdown-list-link" onclick="' . htmlspecialchars($onClickCode) . '" target="content">' . $icon . ' ' . $label . '</a>
				</li>';
        }
        return $entry;
    }

    /**
     * No additional attributes
     *
     * @return string List item HTML attibutes
     */
    public function getAdditionalAttributes()
    {
        return [];
    }

    /**
     * This item has a drop down
     *
     * @return bool
     */
    public function hasDropDown()
    {
        return true;
    }

    /*******************
     ***    HOOKS    ***
     *******************/
    /**
     * Called as a hook in \TYPO3\CMS\Backend\Utility\BackendUtility::setUpdateSignal, calls a JS function to change
     * the number of opened documents
     *
     * @param array $params
     * @param unknown_type $ref
     * @return string list item HTML attributes
     */
    public function updateNumberOfOpenDocsHook(&$params, $ref)
    {
        $params['JScode'] = '
			if (top && top.TYPO3.OpendocsMenu) {
				top.TYPO3.OpendocsMenu.updateMenu();
			}
		';
    }

    /******************
     *** AJAX CALLS ***
     ******************/
    /**
     * Closes a document in the session and
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function closeDocument(ServerRequestInterface $request, ResponseInterface $response)
    {
        $backendUser = $this->getBackendUser();
        $md5sum = isset($request->getParsedBody()['md5sum']) ? $request->getParsedBody()['md5sum'] : $request->getQueryParams()['md5sum'];
        if ($md5sum && isset($this->openDocs[$md5sum])) {
            // Add the document to be closed to the recent documents
            $this->recentDocs = array_merge([$md5sum => $this->openDocs[$md5sum]], $this->recentDocs);
            // Allow a maximum of 8 recent documents
            if (count($this->recentDocs) > 8) {
                $this->recentDocs = array_slice($this->recentDocs, 0, 8);
            }
            // Remove it from the list of the open documents, and store the status
            unset($this->openDocs[$md5sum]);
            list(, $docDat) = $backendUser->getModuleData('FormEngine', 'ses');
            $backendUser->pushModuleData('FormEngine', [$this->openDocs, $docDat]);
            $backendUser->pushModuleData('opendocs::recent', $this->recentDocs);
        }
        return $this->renderMenu($request, $response);
    }

    /**
     * Renders the menu so that it can be returned as response to an AJAX call
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function renderMenu(ServerRequestInterface $request, ResponseInterface $response)
    {
        $response->getBody()->write($this->getDropDown());
        $response = $response->withHeader('Content-Type', 'text/html; charset=utf-8');
        return $response;
    }

    /**
     * Position relative to others
     *
     * @return int
     */
    public function getIndex()
    {
        return 30;
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns current PageRenderer
     *
     * @return PageRenderer
     */
    protected function getPageRenderer()
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Return DatabaseConnection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
