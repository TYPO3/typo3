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
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\PageRendererBackendSetupTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The "move page" wizard. Reachable via list module "Move page" on page records.
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
final readonly class MovePageController
{
    use PageRendererBackendSetupTrait;

    public function __construct(
        private PageRenderer $pageRenderer,
        private BackendViewFactory $backendViewFactory,
        private UriBuilder $uriBuilder,
        private LanguageServiceFactory $languageServiceFactory,
        private ExtensionConfiguration $extensionConfiguration
    ) {}

    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->setUpBasicPageRendererForBackend(
            $this->pageRenderer,
            $this->extensionConfiguration,
            $request,
            $this->languageServiceFactory->createFromUserPreferences($this->getBackendUser())
        );
        $view = $this->backendViewFactory->create($request);
        $queryParams = $request->getQueryParams();
        $contentOnly = $queryParams['contentOnly'] ?? false;
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/tree/page-browser.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/viewport/resizable-navigation.js');
        $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@typo3/backend/wizard/move-page.js', 'MovePage')->instance()
        );
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_core.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_misc.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:backend/Resources/Private/Language/Wizards/move_page.xlf');

        $targetPid = (int)($queryParams['expandPage'] ?? 0);
        $pageIdToMove = (int)($queryParams['uid'] ?? 0);
        $makeCopy = (bool)($queryParams['makeCopy'] ?? 0);

        if ($targetPid) {
            $view->assignMultiple($this->getContentVariables($pageIdToMove, $targetPid));
        }
        $view->assignMultiple([
            'activePage' => $targetPid,
            'contentOnly' => $contentOnly,
            // Make-copy checkbox (clicking this will reload the page with the GET var makeCopy set differently):
            'makeCopyChecked' => $makeCopy,
            'makeCopyUrl' => $this->uriBuilder->buildUriFromRoute(
                'move_page',
                [
                    'uid' => $pageIdToMove,
                    'makeCopy' => !$makeCopy,
                ]
            ),
        ]);

        $content = $view->render('Page/MovePage');
        if ($contentOnly) {
            return new HtmlResponse($content);
        }
        $this->pageRenderer->setBodyContent('<body>' . $content);
        return new HtmlResponse($this->pageRenderer->render());
    }

    private function getContentVariables(int $pageIdToMove, int $targetPid): array
    {
        $elementRow = BackendUtility::getRecordWSOL('pages', $pageIdToMove);
        $targetRow = BackendUtility::getRecordWSOL('pages', $targetPid);
        if (!$this->getBackendUser()->doesUserHaveAccess($targetRow, Permission::PAGE_EDIT)) {
            return [];
        }
        return [
            'targetHasSubpages' => $this->pageHasSubpages($targetPid),
            'element' => [
                'record' => $elementRow,
                'recordTooltip' => BackendUtility::getRecordIconAltText($elementRow, 'pages', false),
                'recordTitle' => BackendUtility::getRecordTitle('pages', $elementRow),
                'recordPath' => BackendUtility::getRecordPath($pageIdToMove, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW), 0),
            ],
            'target' => [
                'record' => $targetRow,
                'recordTooltip' => BackendUtility::getRecordIconAltText($targetRow, 'pages', false),
                'recordTitle' => BackendUtility::getRecordTitle('pages', $targetRow),
                'recordPath' => BackendUtility::getRecordPath($targetPid, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW), 0),
            ],
            'positions' => [
                'above' => $this->getTargetForAboveInsert($targetRow),
                'inside' => $targetRow['uid'],
                'below' => $targetRow['uid'] * -1,
            ],
            'hasEditPermissions' => $this->getBackendUser()->doesUserHaveAccess($elementRow, Permission::PAGE_EDIT),
            'isDifferentPage' => $pageIdToMove !== $targetRow['uid'],
        ];
    }

    protected function getTargetForAboveInsert(array $targetRow): int
    {
        $targetPageId = (int)$targetRow['uid'];
        $subpages = $this->getSubpagesForPageId($targetRow['pid']);
        if (in_array($targetPageId, $subpages, true)) {
            // Set pointer in array to $targetPid
            while (current($subpages) !== $targetPageId) {
                if (next($subpages) === false) {
                    // We reached the end of the array and couldn't find the target pid (how?). Fall back to pid
                    return (int)$targetRow['pid'];
                }
            }
            $previousItem = prev($subpages);
            if ($previousItem !== false) {
                return $previousItem * -1;
            }
        }

        return (int)$targetRow['pid'];
    }

    protected function getSubpagesForPageId(int $pageId): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));

        return $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageId)),
                $queryBuilder->expr()->eq('sys_language_uid', 0),
                QueryHelper::stripLogicalOperatorPrefix(
                    $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW)
                )
            )
            ->orderBy('sorting')
            ->executeQuery()
            ->fetchFirstColumn();
    }

    protected function pageHasSubpages(int $pageId): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));

        $count = (int)$queryBuilder
            ->count('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageId)),
                $queryBuilder->expr()->eq('sys_language_uid', 0),
                QueryHelper::stripLogicalOperatorPrefix(
                    $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW)
                )
            )
            ->executeQuery()
            ->fetchOne();

        return $count > 0;
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
