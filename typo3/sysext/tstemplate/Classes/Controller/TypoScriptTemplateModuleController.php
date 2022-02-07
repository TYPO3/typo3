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

namespace TYPO3\CMS\Tstemplate\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper;

/**
 * Module: TypoScript Tools
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class TypoScriptTemplateModuleController
{
    protected ?ModuleTemplate $view;
    protected ModuleData $moduleData;
    protected ModuleInterface $currentModule;
    protected ServerRequestInterface $request;
    protected ExtendedTemplateService $templateService;

    public array $pageinfo = [];
    protected string $perms_clause = '';
    protected bool $access = false;

    /**
     * Value of the GET/POST var 'id' = the current page ID
     */
    protected int $id;

    /**
     * The currently selected sys_template record or false if non is selected
     */
    protected array|false $templateRow;

    public function __construct(
        protected readonly IconFactory $iconFactory,
        protected readonly PageRenderer $pageRenderer,
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly BackendViewFactory $backendViewFactory,
    ) {
    }

    protected function init(ServerRequestInterface $request): void
    {
        $this->request = $request;
        $this->currentModule = $request->getAttribute('module');
        $this->moduleData = $request->getAttribute('moduleData');
        $backendUser = $this->getBackendUser();
        $allowedModuleOptions = $this->getAllowedModuleOptions();
        if ($this->moduleData->cleanUp($allowedModuleOptions)) {
            $backendUser->pushModuleData($this->moduleData->getModuleIdentifier(), $this->moduleData->toArray());
        }
        $this->view = $this->moduleTemplateFactory->create($request);
        $this->templateService = GeneralUtility::makeInstance(ExtendedTemplateService::class);
        $this->getLanguageService()->includeLLFile('EXT:tstemplate/Resources/Private/Language/locallang.xlf');
        $this->id = (int)($request->getParsedBody()['id'] ?? $request->getQueryParams()['id'] ?? 0);
        $this->view->setTitle(
            $this->getLanguageService()->sL($this->currentModule->getTitle()),
            $this->id !== 0 && isset($this->pageinfo['title']) ? $this->pageinfo['title'] : ''
        );
        $this->view->assign('moduleIdentifier', $this->currentModule->getIdentifier());

        // Access check...
        // The page will show only if there is a valid page and if this page may be viewed by the user
        $this->perms_clause = $backendUser->getPagePermsClause(Permission::PAGE_SHOW);
        $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause) ?: [];
        $this->access = $this->pageinfo !== [];
        // The page will show only if there is a valid page and if this page
        // may be viewed by the user
        if ($this->access) {
            $this->view->getDocHeaderComponent()->setMetaInformation($this->pageinfo);
        }
        $this->getButtons();
        $this->view->assign('pageId', $this->id);
        $this->view->makeDocHeaderModuleMenu(['id' => $this->id]);
    }

    /**
     * Injects the request object for the current request or subrequest
     * Then checks for module functions that have hooked in, and renders menu etc.
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->init($request);
        return $this->overviewAction();
    }

    /**
     * Renders a list of all pages
     */
    protected function overviewAction(): ResponseInterface
    {
        if ($this->id && $this->access) {
            // Build the module content
            return $this->view->renderResponse('Main');
        }
        $workspaceId = $this->getBackendUser()->workspace;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_template');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $this->applyWorkspaceConstraint(
            $queryBuilder,
            'sys_template',
            $workspaceId
        );
        $result = $queryBuilder
            ->select(
                'uid',
                'pid',
                'title',
                'root',
                'hidden',
                'starttime',
                'endtime',
                't3ver_oid',
                't3ver_wsid',
                't3ver_state'
            )
            ->from('sys_template')
            ->orderBy('sys_template.pid')
            ->addOrderBy('sys_template.sorting')
            ->executeQuery();
        $pArray = [];
        while ($record = $result->fetchAssociative()) {
            BackendUtility::workspaceOL('sys_template', $record, $workspaceId, true);
            if (empty($record) || VersionState::cast($record['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)) {
                continue;
            }
            $additionalFieldsForRootline = ['sorting', 'shortcut'];
            $rootline = BackendUtility::BEgetRootLine($record['pid'], '', true, $additionalFieldsForRootline);
            $this->setInPageArray($pArray, $rootline, $record);
        }

        $this->view->assign('pageTree', $pArray);
        $this->view->assign('moduleIdentifier', $this->currentModule->getIdentifier());
        return $this->view->renderResponse('PageZero');
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     */
    protected function getButtons(): void
    {
        $buttonBar = $this->view->getDocHeaderComponent()->getButtonBar();
        $lang = $this->getLanguageService();

        if ($this->id
            && $this->access
            && !in_array((int)$this->pageinfo['doktype'], $this->getExcludeDoktypes(), true)
        ) {
            // View page
            $previewDataAttributes = PreviewUriBuilder::create($this->id)
                ->withRootLine(BackendUtility::BEgetRootLine($this->id))
                ->buildDispatcherDataAttributes();
            $viewButton = $buttonBar->makeLinkButton()
                ->setHref('#')
                ->setDataAttributes($previewDataAttributes ?? [])
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
                ->setIcon($this->iconFactory->getIcon('actions-view-page', Icon::SIZE_SMALL));
            $buttonBar->addButton($viewButton, ButtonBar::BUTTON_POSITION_LEFT, 99);
        }

        // Shortcut
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier($this->currentModule->getIdentifier())
            ->setDisplayName($this->getShortcutTitle())
            ->setArguments(['id' => $this->id]);
        $buttonBar->addButton($shortcutButton);
    }

    /**
     * Wrap title for link to template in the template overview module, called from client classes.
     */
    protected function linkWrapTemplateTitle(string $title, string $onlyKey = ''): string
    {
        $urlParameters = [
            'id' => $this->id,
        ];
        // @todo It seems like the "e" parameter is never evaluated
        if ($onlyKey) {
            $urlParameters['e'] = [$onlyKey => 1];
        } else {
            $urlParameters['e'] = ['constants' => 1];
        }
        return '
            <a href="' . htmlspecialchars((string)$this->uriBuilder->buildUriFromRoute('web_typoscript_overview', $urlParameters)) . '">
                ' . htmlspecialchars($title) . '
            </a>';
    }

    /**
     * No template
     */
    protected function noTemplateAction(): ResponseInterface
    {
        // Go to previous Page with a template
        $this->view->assign('previousPage', $this->getClosestAncestorPageWithTemplateRecord($this->id, $this->perms_clause));
        $this->view->assign('content', ['state' => InfoboxViewHelper::STATE_INFO]);
        return $this->view->renderResponse('NoTemplate');
    }

    /**
     * Render template menu, called from client classes.
     */
    protected function templateMenu(): string
    {
        $all = $this->getAllTemplateRecordsForPage();
        $templatesOnPage = [];
        if (count($all) > 1) {
            foreach ($all as $d) {
                $templatesOnPage[(int)$d['uid']] = $d['title'];
            }
        }
        if (($templatesOnPage !== [])
            && $this->moduleData->clean('templatesOnPage', array_keys($templatesOnPage))
        ) {
            $this->getBackendUser()->pushModuleData($this->moduleData->getModuleIdentifier(), $this->moduleData->toArray());
        }
        return BackendUtility::getFuncMenu(
            $this->id,
            'templatesOnPage',
            $this->moduleData->get('templatesOnPage'),
            $templatesOnPage
        );
    }

    /**
     * Create template, called from client classes.
     */
    protected function createTemplate(int $actTemplateId = 0): int
    {
        $recData = [];
        $tce = GeneralUtility::makeInstance(DataHandler::class);

        if ($this->request->getParsedBody()['createExtension'] ?? $this->request->getQueryParams()['createExtension'] ?? false) {
            $recData['sys_template']['NEW'] = [
                'pid' => $actTemplateId ? -1 * $actTemplateId : $this->id,
                'title' => '+ext',
            ];
            $tce->start($recData, []);
            $tce->process_datamap();
        } elseif ($this->request->getParsedBody()['newWebsite'] ?? $this->request->getQueryParams()['newWebsite'] ?? false) {
            $recData['sys_template']['NEW'] = [
                'pid' => $this->id,
                'title' => $this->getLanguageService()->getLL('titleNewSite'),
                'sorting' => 0,
                'root' => 1,
                'clear' => 3,
                'config' => '
# Default PAGE object:
page = PAGE
page.10 = TEXT
page.10.value = HELLO WORLD!
',
            ];
            $tce->start($recData, []);
            $tce->process_datamap();
        }
        return (int)($tce->substNEWwithIDs['NEW'] ?? 0);
    }

    /**
     * Set page in array
     * To render list of page tree with templates
     */
    protected function setInPageArray(array &$pages, array $rootline, array $row): void
    {
        ksort($rootline);
        reset($rootline);
        if (!$rootline[0]['uid']) {
            array_shift($rootline);
        }
        $cEl = current($rootline);
        if (empty($pages[$cEl['uid']])) {
            $pages[$cEl['uid']] = $cEl;
        }
        array_shift($rootline);
        if (!empty($rootline)) {
            if (empty($pages[$cEl['uid']]['_nodes'])) {
                $pages[$cEl['uid']]['_nodes'] = [];
            }
            $this->setInPageArray($pages[$cEl['uid']]['_nodes'], $rootline, $row);
        } else {
            $pages[$cEl['uid']]['_templates'][] = $row;
        }
        uasort($pages, static function ($a, $b) {
            return $a['sorting'] - $b['sorting'];
        });
    }

    /**
     * Fetching all live records, and versioned records that do not have a "online ID" counterpart,
     * as this is then handled via the BackendUtility::workspaceOL().
     *
     * @param QueryBuilder $queryBuilder
     * @param string $tableName
     * @param int $workspaceId
     */
    protected function applyWorkspaceConstraint(
        QueryBuilder $queryBuilder,
        string $tableName,
        int $workspaceId
    ): void {
        if (!BackendUtility::isTableWorkspaceEnabled($tableName)) {
            return;
        }

        $queryBuilder->getRestrictions()->add(
            GeneralUtility::makeInstance(WorkspaceRestriction::class, $workspaceId)
        );
    }

    /**
     * Returns the shortcut title for the current page
     *
     * @return string
     */
    protected function getShortcutTitle(): string
    {
        return sprintf(
            '%s: %s [%d]',
            $this->getLanguageService()->sL('LLL:EXT:tstemplate/Resources/Private/Language/locallang_mod.xlf:mlang_labels_tablabel'),
            BackendUtility::getRecordTitle('pages', $this->pageinfo),
            $this->id
        );
    }

    protected function getClosestAncestorPageWithTemplateRecord($id, string $perms_clause): array
    {
        $rootLine = BackendUtility::BEgetRootLine($id, $perms_clause ? ' AND ' . $perms_clause : '');
        foreach ($rootLine as $p) {
            if ($this->getFirstTemplateRecordOnPage((int)$p['uid'])) {
                return $p;
            }
        }
        return [];
    }

    /**
     * Get an array of all template records on a page.
     */
    protected function getAllTemplateRecordsForPage(): array
    {
        if (!$this->id) {
            return [];
        }
        $result = $this->getTemplateQueryBuilder($this->id)->executeQuery();
        $outRes = [];
        while ($row = $result->fetchAssociative()) {
            BackendUtility::workspaceOL('sys_template', $row);
            if (is_array($row)) {
                $outRes[] = $row;
            }
        }
        return $outRes;
    }

    /**
     * Get a single sys_template record attached to a single page.
     * If multiple template records are on this page, the first (order by sorting)
     * record will be returned, unless a specific template uid is specified via $templateUid
     *
     * @param int $pid The pid to select sys_template records from
     * @param int $templateUid Optional template uid
     * @return array<string,mixed>|false Returns the template record or false if none was found
     */
    protected function getFirstTemplateRecordOnPage(int $pid, int $templateUid = 0): array|false
    {
        if (empty($pid)) {
            return false;
        }

        // Query is taken from the runThroughTemplates($theRootLine) function in the parent class.
        $queryBuilder = $this->getTemplateQueryBuilder($pid)
            ->setMaxResults(1);
        if ($templateUid) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($templateUid, \PDO::PARAM_INT))
            );
        }
        $row = $queryBuilder->executeQuery()->fetchAssociative();
        BackendUtility::workspaceOL('sys_template', $row);

        return $row;
    }

    /**
     * Internal helper method to prepare the query builder for
     * getting sys_template records from a given pid
     *
     * @param int $pid The pid to select sys_template records from
     * @return QueryBuilder Returns a QueryBuilder
     */
    protected function getTemplateQueryBuilder(int $pid): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_template');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $GLOBALS['BE_USER']->workspace));

        $queryBuilder->select('*')
            ->from('sys_template')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT))
            );
        if (!empty($GLOBALS['TCA']['sys_template']['ctrl']['sortby'])) {
            $queryBuilder->orderBy($GLOBALS['TCA']['sys_template']['ctrl']['sortby']);
        }

        return $queryBuilder;
    }

    protected function getExcludeDoktypes(): array
    {
        $pagesTSconfig = BackendUtility::getPagesTSconfig($this->id);
        if (isset($pagesTSconfig['TCEMAIN.']['preview.']['disableButtonForDokType'])) {
            return GeneralUtility::intExplode(
                ',',
                $pagesTSconfig['TCEMAIN.']['preview.']['disableButtonForDokType'],
                true
            );
        }

        // exclude sysfolders and recycler by default
        return [
            PageRepository::DOKTYPE_RECYCLER,
            PageRepository::DOKTYPE_SYSFOLDER,
            PageRepository::DOKTYPE_SPACER,
        ];
    }

    protected function getAllowedModuleOptions(): array
    {
        // Extending classes can add their allowed module options here
        return [];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
