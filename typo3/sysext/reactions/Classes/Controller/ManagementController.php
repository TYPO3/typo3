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

namespace TYPO3\CMS\Reactions\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\CodeEditor\CodeEditorConfiguration;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\Components\MultiRecordSelection\Action;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reactions\Http\PayloadDecoder;
use TYPO3\CMS\Reactions\Model\ReactionExample;
use TYPO3\CMS\Reactions\Model\ReactionInstruction;
use TYPO3\CMS\Reactions\Pagination\DemandedArrayPaginator;
use TYPO3\CMS\Reactions\Reaction\CreateRecordDryRun;
use TYPO3\CMS\Reactions\Reaction\CreateRecordReaction;
use TYPO3\CMS\Reactions\ReactionRegistry;
use TYPO3\CMS\Reactions\Repository\ReactionDemand;
use TYPO3\CMS\Reactions\Repository\ReactionRepository;

/**
 * The Administration > Integrations > Reaction module: Rendering the listing of reactions.
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
#[AsController]
readonly class ManagementController
{
    public function __construct(
        private UriBuilder $uriBuilder,
        private IconFactory $iconFactory,
        private ModuleTemplateFactory $moduleTemplateFactory,
        private ReactionRegistry $reactionRegistry,
        private ReactionRepository $reactionRepository,
        private ComponentFactory $componentFactory,
        private BackendViewFactory $backendViewFactory,
        private CodeEditorConfiguration $codeEditorConfiguration,
        private CreateRecordDryRun $createRecordDryRun,
        private TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);
        $demand = ReactionDemand::fromRequest($request);
        $count = $this->reactionRepository->countAll($demand);
        $demand = $demand->withPage(max(1, min($demand->getPage(), (int)ceil($count / $demand->getLimit()))));

        $this->registerDocHeaderButtons($view, $demand);
        $view->makeDocHeaderModuleMenu();

        $reactionRecords = $this->reactionRepository->getReactionRecords($demand);
        $paginator = new DemandedArrayPaginator($reactionRecords, $demand->getPage(), $demand->getLimit(), $count);
        $pagination = new SimplePagination($paginator);

        $requestUri = $request->getAttribute('normalizedParams')->getRequestUri();
        $languageService = $this->getLanguageService();

        return $view->assignMultiple([
            'demand' => $demand,
            'reactionTypes' => iterator_to_array($this->reactionRegistry->getAvailableReactionTypes()),
            'paginator' => $paginator,
            'pagination' => $pagination,
            'rowDetails' => $this->getRowDetails($paginator->getPaginatedItems()),
            'impersonatedUsers' => $this->reactionRepository->findImpersonatedUsers(),
            'dateFormat' => [
                'day' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] ?? 'd-m-y',
                'time' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'] ?? 'H:i',
            ],
            'actions' => [
                new Action(
                    'edit',
                    [
                        'idField' => 'uid',
                        'tableName' => 'sys_reaction',
                        'returnUrl' => $requestUri,
                    ],
                    'actions-open',
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.edit'
                ),
                new Action(
                    'delete',
                    [
                        'idField' => 'uid',
                        'tableName' => 'sys_reaction',
                        'title' => $languageService->sL('LLL:EXT:reactions/Resources/Private/Language/module.xlf:labels.delete.title'),
                        'content' => $languageService->sL('LLL:EXT:reactions/Resources/Private/Language/module.xlf:labels.delete.message'),
                        'ok' => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.delete'),
                        'cancel' => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.cancel'),
                        'returnUrl' => $requestUri,
                    ],
                    'actions-edit-delete',
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.delete'
                ),
            ],
        ])->renderResponse('Management/Overview');
    }

    /**
     * The AJAX route inherits the access of the module, which is admin only. It is checked
     * here as well, since the actions are not bound to be reached through that route.
     */
    public function tryOutAction(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->getBackendUser()->isAdmin()) {
            return new HtmlResponse('', 403);
        }
        $reaction = $this->reactionRepository->getReactionRecordByUid($this->getRequestedReactionUid($request));
        if ($reaction === null) {
            return new HtmlResponse('', 404);
        }
        $url = (string)$this->uriBuilder->buildUriFromRoute(
            'reaction',
            ['reactionIdentifier' => $reaction->getIdentifier()],
            UriBuilder::ABSOLUTE_URL
        );
        $createRecordReaction = $this->getCreateRecordReaction($reaction);
        $example = ReactionExample::forFieldMap(
            $url,
            $createRecordReaction !== null ? (array)($reaction->toArray()['fields'] ?? []) : []
        );
        $view = $this->backendViewFactory->create($request);
        $view->assign('example', $example);
        if ($createRecordReaction !== null) {
            $view->assign('dryRun', [
                'url' => (string)$this->uriBuilder->buildUriFromRoute('ajax_reactions_dry_run', ['reaction' => $reaction->getUid()]),
                'payload' => $example->getFormattedPayload(),
                'editorMode' => GeneralUtility::jsonEncodeForHtmlAttribute($this->getJsonEditorMode(), false),
            ]);
        }

        return new HtmlResponse($view->render('Management/TryOut'));
    }

    public function resolveDryRunAction(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->getBackendUser()->isAdmin()) {
            return new HtmlResponse('', 403);
        }
        $reaction = $this->reactionRepository->getReactionRecordByUid($this->getRequestedReactionUid($request));
        $createRecordReaction = $reaction !== null ? $this->getCreateRecordReaction($reaction) : null;
        if ($createRecordReaction === null) {
            return new HtmlResponse('', 404);
        }

        $payload = PayloadDecoder::decode($this->getSubmittedPayload($request));
        $view = $this->backendViewFactory->create($request);
        $view->assignMultiple([
            'result' => $this->createRecordDryRun->run($reaction, $createRecordReaction, $payload),
            'isValidPayload' => $payload !== null,
        ]);

        return new HtmlResponse($view->render('Management/DryRunResult'));
    }

    /**
     * @param iterable<ReactionInstruction> $reactions
     * @return array<int, array{table: string, page: array{uid: int, title: string, exists: bool}, user: array{uid: int, username: string, disabled: bool, exists: bool}, lastCall: array{time: int, status: int, successful: bool, failure: string}}>
     */
    protected function getRowDetails(iterable $reactions): array
    {
        $pages = [];
        $users = [];
        $details = [];
        foreach ($reactions as $reaction) {
            $record = $reaction->toArray();
            $table = (string)($record['table_name'] ?? '');
            $tableTitle = $table;
            if ($table !== '' && $this->tcaSchemaFactory->has($table)) {
                $tableTitle = $this->tcaSchemaFactory->get($table)->getTitle($this->getLanguageService()->sL(...)) ?: $table;
            }

            $pageUid = (int)($record['storage_pid'] ?? 0);
            $pages[$pageUid] ??= $this->getPageDetails($pageUid);
            $userUid = (int)($record['impersonate_user'] ?? 0);
            $users[$userUid] ??= $this->getUserDetails($userUid);
            $lastStatus = (int)($record['last_status'] ?? 0);

            $details[$reaction->getUid()] = [
                'table' => $tableTitle,
                'page' => $pages[$pageUid],
                'user' => $users[$userUid],
                'lastCall' => [
                    'time' => (int)($record['last_called'] ?? 0),
                    'status' => $lastStatus,
                    'successful' => $lastStatus >= 200 && $lastStatus < 300,
                    'failure' => (string)($record['last_failure'] ?? ''),
                ],
            ];
        }
        return $details;
    }

    /**
     * @return array{uid: int, title: string, exists: bool}
     */
    protected function getPageDetails(int $uid): array
    {
        if ($uid === 0) {
            return ['uid' => 0, 'title' => (string)($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ?? ''), 'exists' => true];
        }
        $page = BackendUtility::getRecord('pages', $uid);
        return [
            'uid' => $uid,
            'title' => $page !== null ? BackendUtility::getRecordTitle('pages', $page) : '',
            'exists' => $page !== null,
        ];
    }

    /**
     * @return array{uid: int, username: string, disabled: bool, exists: bool}
     */
    protected function getUserDetails(int $uid): array
    {
        $user = $uid > 0 ? BackendUtility::getRecord('be_users', $uid) : null;
        return [
            'uid' => $uid,
            'username' => (string)($user['username'] ?? ''),
            'disabled' => (bool)($user['disable'] ?? false),
            'exists' => $user !== null,
        ];
    }

    protected function getJsonEditorMode(): JavaScriptModuleInstruction
    {
        $mode = $this->codeEditorConfiguration->hasMode('json')
            ? $this->codeEditorConfiguration->getModeByFormatCode('json')
            : $this->codeEditorConfiguration->getDefaultMode();
        return $mode->module;
    }

    protected function getCreateRecordReaction(ReactionInstruction $reaction): ?CreateRecordReaction
    {
        $implementation = $this->reactionRegistry->getReactionByType($reaction->getType());
        return $implementation instanceof CreateRecordReaction ? $implementation : null;
    }

    protected function getRequestedReactionUid(ServerRequestInterface $request): int
    {
        $uid = $request->getQueryParams()['reaction'] ?? 0;
        return is_scalar($uid) ? (int)$uid : 0;
    }

    protected function getSubmittedPayload(ServerRequestInterface $request): string
    {
        $body = $request->getParsedBody();
        $payload = is_array($body) ? ($body['payload'] ?? '') : '';
        return is_string($payload) ? $payload : '';
    }

    protected function registerDocHeaderButtons(ModuleTemplate $view, ReactionDemand $demand): void
    {
        $languageService = $this->getLanguageService();

        $newRecordButton = $this->componentFactory->createLinkButton()
            ->setHref((string)$this->uriBuilder->buildUriFromRoute(
                'record_edit',
                [
                    'edit' => ['sys_reaction' => ['new']],
                    'module' => 'integrations_reactions',
                    'returnUrl' => (string)$this->uriBuilder->buildUriFromRoute('integrations_reactions'),
                ]
            ))
            ->setShowLabelText(true)
            ->setTitle($languageService->sL('LLL:EXT:reactions/Resources/Private/Language/module.xlf:reaction_create'))
            ->setIcon($this->iconFactory->getIcon('actions-plus', IconSize::SMALL));
        $view->getDocHeaderComponent()->getButtonBar()->addButton($newRecordButton);

        $view->getDocHeaderComponent()->setShortcutContext(
            'integrations_reactions',
            $languageService->sL('LLL:EXT:reactions/Resources/Private/Language/module.xlf:title'),
            array_filter([
                'demand' => $demand->getParameters(),
                'orderField' => $demand->getOrderField(),
                'orderDirection' => $demand->getOrderDirection(),
            ])
        );
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
