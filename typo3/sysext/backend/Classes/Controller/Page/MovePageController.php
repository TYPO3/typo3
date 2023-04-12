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

namespace TYPO3\CMS\Backend\Controller\Page;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Tree\View\PageMovingPagePositionMap;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The "move page" wizard. Reachable via list module "Move page" on page records.
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[Controller]
final class MovePageController
{
    private int $page_id = 0;
    private string $R_URI = '';
    private int $moveUid = 0;
    private int $makeCopy = 0;
    private string $perms_clause = '';

    public function __construct(
        private readonly IconFactory $iconFactory,
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly UriBuilder $uriBuilder,
    ) {
    }

    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $this->page_id = (int)($parsedBody['uid'] ?? $queryParams['uid'] ?? 0);
        $this->R_URI = GeneralUtility::sanitizeLocalUrl($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? '');
        $this->moveUid = (int)(($parsedBody['moveUid'] ?? $queryParams['moveUid'] ?? false) ?: $this->page_id);
        $this->makeCopy = (int)($parsedBody['makeCopy'] ?? $queryParams['makeCopy'] ?? 0);
        // Select-pages where clause for read-access
        $this->perms_clause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);

        // Setting up the buttons and markers for docheader
        $this->getButtons($view);
        $view->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:movingElement'));
        $view->assignMultiple($this->getContentVariables($request));
        return $view->renderResponse('Page/MovePage');
    }

    private function getContentVariables(ServerRequestInterface $request): array
    {
        if (!$this->page_id) {
            return [];
        }
        $queryParams = $request->getQueryParams();
        $assigns = [];
        $backendUser = $this->getBackendUser();
        // Get record for element:
        $elRow = BackendUtility::getRecordWSOL('pages', $this->moveUid);
        // Headerline: Icon, record title:
        $assigns['record'] = $elRow;
        $assigns['recordTooltip'] = BackendUtility::getRecordIconAltText($elRow, 'pages');
        $assigns['recordTitle'] = BackendUtility::getRecordTitle('pages', $elRow, true);
        // Make-copy checkbox (clicking this will reload the page with the GET var makeCopy set differently):
        $assigns['makeCopyChecked'] = (bool)$this->makeCopy;
        $assigns['makeCopyUrl'] = $this->uriBuilder->buildUriFromRoute(
            'move_page',
            [
                'uid' => $queryParams['uid'] ?? 0,
                'makeCopy' => !$this->makeCopy,
                'returnUrl' => $queryParams['returnUrl'] ?? '',
            ]
        );
        $pageInfo = BackendUtility::readPageAccess($this->page_id, $this->perms_clause);
        $assigns['pageInfo'] = $pageInfo;
        if (is_array($pageInfo) && $backendUser->isInWebMount($pageInfo['pid'], $this->perms_clause)) {
            // Initialize the page position map:
            $pagePositionMap = GeneralUtility::makeInstance(PageMovingPagePositionMap::class);
            $pagePositionMap->moveOrCopy = $this->makeCopy ? 'copy' : 'move';
            $pagePositionMap->moveUid = $this->moveUid;
            // Print a "go-up" link IF there is a real parent page (and if the user has read-access to that page).
            if ($pageInfo['pid']) {
                $pidPageInfo = BackendUtility::readPageAccess($pageInfo['pid'], $this->perms_clause);
                if (is_array($pidPageInfo)) {
                    if ($backendUser->isInWebMount($pidPageInfo['pid'], $this->perms_clause)) {
                        $assigns['goUpUrl'] = $this->uriBuilder->buildUriFromRoute(
                            'move_page',
                            [
                                'uid' => (int)$pageInfo['pid'],
                                'moveUid' => $this->moveUid,
                                'makeCopy' => $this->makeCopy,
                                'returnUrl' => $queryParams['returnUrl'] ?? '',
                            ]
                        );
                    } else {
                        $assigns['pidPageInfo'] = $pidPageInfo;
                    }
                    $assigns['pidRecordTitle'] = BackendUtility::getRecordTitle('pages', $pidPageInfo, true);
                }
            }
            // Create the position tree:
            $assigns['positionTree'] = $pagePositionMap->positionTree($this->page_id, $pageInfo, $this->perms_clause, $this->R_URI, $request);
        }
        return $assigns;
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     */
    private function getButtons(ModuleTemplate $view): void
    {
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
        if ($this->page_id) {
            if ($this->R_URI) {
                $backButton = $buttonBar->makeLinkButton()
                    ->setHref($this->R_URI)
                    ->setShowLabelText(true)
                    ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:goBack'))
                    ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
                $buttonBar->addButton($backButton);
            }
        }
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
