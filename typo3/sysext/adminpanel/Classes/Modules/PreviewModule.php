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

namespace TYPO3\CMS\Adminpanel\Modules;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractModule;
use TYPO3\CMS\Adminpanel\ModuleApi\OnSubmitActorInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\PageSettingsProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\RequestEnricherInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ResourceProviderInterface;
use TYPO3\CMS\Adminpanel\Repositories\FrontendGroupsRepository;
use TYPO3\CMS\Core\Authentication\GroupResolver;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Domain\DateTimeFactory;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Frontend\Aspect\PreviewAspect;
use TYPO3\CMS\Frontend\Cache\CacheInstruction;

/**
 * Admin Panel Preview Module
 */
#[Autoconfigure(public: true)]
class PreviewModule extends AbstractModule implements RequestEnricherInterface, PageSettingsProviderInterface, ResourceProviderInterface, OnSubmitActorInterface
{
    /**
     * module configuration, set on initialize
     *
     * @var array{
     *     showHiddenPages?: bool,
     *     simulateDate?: int,
     *     showHiddenRecords?: bool,
     *     showScheduledRecords?: bool,
     *     simulateUserGroup?: int,
     *     showFluidDebug?: bool
     * }
     */
    protected array $config;

    public function __construct(
        protected readonly CacheManager $cacheManager,
        private readonly ViewFactoryInterface $viewFactory,
        private readonly LoggerInterface $logger,
        private readonly GroupResolver $groupResolver,
    ) {}

    public function getIconIdentifier(): string
    {
        return 'actions-preview';
    }

    public function getIdentifier(): string
    {
        return 'preview';
    }

    public function getLabel(): string
    {
        return $this->getLanguageService()->sL(
            'LLL:EXT:adminpanel/Resources/Private/Language/locallang_preview.xlf:module.label'
        );
    }

    public function enrich(ServerRequestInterface $request): ServerRequestInterface
    {
        // Backend preview params (ADMCMD_) take precedence over configured admin panel values
        $simulateGroupByRequest = (int)($request->getQueryParams()['ADMCMD_simUser'] ?? 0);
        $simulateTimeByRequest = (int)($request->getQueryParams()['ADMCMD_simTime'] ?? 0);
        $this->config = [
            'showHiddenPages' => (bool)$this->getConfigOptionForModule('showHiddenPages'),
            'simulateDate' => $simulateTimeByRequest ?: (int)$this->getConfigOptionForModule('simulateDate'),
            'showHiddenRecords' => (bool)$this->getConfigOptionForModule('showHiddenRecords'),
            'showScheduledRecords' => (bool)$this->getConfigOptionForModule('showScheduledRecords'),
            'simulateUserGroup' => $simulateGroupByRequest ?: (int)$this->getConfigOptionForModule('simulateUserGroup'),
            'showFluidDebug' => (bool)$this->getConfigOptionForModule('showFluidDebug'),
        ];
        if ($this->config['showFluidDebug']) {
            // forcibly unset fluid caching as it does not care about the tsfe based caching settings
            // @todo: Useless?! CacheManager is initialized via bootstrap already, TYPO3_CONF_VARS is not read again?
            unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['fluid_template']['frontend']);
            $cacheInstruction = $request->getAttribute('frontend.cache.instruction', new CacheInstruction());
            $cacheInstruction->disableCache('EXT:adminpanel: "Show fluid debug output" disables cache.');
            $request = $request->withAttribute('frontend.cache.instruction', $cacheInstruction);
        }
        $this->initializeFrontendPreview(
            $this->config['showHiddenPages'],
            $this->config['showHiddenRecords'],
            $this->config['showScheduledRecords'],
            $this->config['simulateDate'],
            $this->config['simulateUserGroup'],
            $request
        );

        return $request;
    }

    public function getPageSettings(): string
    {
        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: ['EXT:adminpanel/Resources/Private/Templates'],
            partialRootPaths: ['EXT:adminpanel/Resources/Private/Partials'],
            layoutRootPaths: ['EXT:adminpanel/Resources/Private/Layouts'],
        );
        $view = $this->viewFactory->create($viewFactoryData);

        $frontendGroupsRepository = GeneralUtility::makeInstance(FrontendGroupsRepository::class);

        $pageId = 0;
        $pageArguments = $GLOBALS['TYPO3_REQUEST']->getAttribute('routing');
        if ($pageArguments instanceof PageArguments) {
            $pageId = $pageArguments->getPageId();
        }

        $view->assignMultiple(
            [
                'show' => [
                    'pageId' => $pageId,
                    'hiddenPages' => $this->config['showHiddenPages'] ?? false,
                    'hiddenRecords' => $this->config['showHiddenRecords'] ?? false,
                    'showScheduledRecords' => $this->config['showScheduledRecords'] ?? false,
                    'fluidDebug' => $this->config['showFluidDebug'] ?? false,
                ],
                'simulateDate' => (int)($this->config['simulateDate'] ?? 0),
                'frontendUserGroups' => [
                    'availableGroups' => $frontendGroupsRepository->getAvailableFrontendUserGroups(),
                    'selected' => (int)($this->config['simulateUserGroup'] ?? 0),
                ],
                'languageKey' => $this->getBackendUser()->user['lang'] ?? null,
            ]
        );
        return $view->render('Modules/Settings/Preview');
    }

    protected function getConfigOptionForModule(string $option): string
    {
        return $this->configurationService->getConfigurationOption(
            'preview',
            $option
        );
    }

    /**
     * Initialize frontend preview functionality incl.
     * simulation of users or time
     *
     * @throws \Exception
     */
    protected function initializeFrontendPreview(
        bool $showHiddenPages,
        bool $showHiddenRecords,
        bool $showScheduledRecords,
        int $simulateDate,
        int $simulateUserGroup,
        ServerRequestInterface $request
    ): void {
        $context = GeneralUtility::makeInstance(Context::class);
        $this->clearPreviewSettings($context);

        // Modify visibility settings (hidden pages + hidden content)
        $context->setAspect('visibility', new VisibilityAspect($showHiddenPages, $showHiddenRecords, false, $showScheduledRecords));

        // Simulate date
        $simTime = null;
        if ($simulateDate) {
            $simTime = max($simulateDate, 60);
            $GLOBALS['SIM_EXEC_TIME'] = $simTime;
            $GLOBALS['SIM_ACCESS_TIME'] = $simTime - $simTime % 60;
            $context->setAspect('date', new DateTimeAspect(DateTimeFactory::createFromTimestamp($simTime)));
        }
        // simulate usergroup
        if ($simulateUserGroup) {
            $frontendUser = $request->getAttribute('frontend.user');
            $frontendUser->user[$frontendUser->usergroup_column] = (string)$simulateUserGroup;
            $frontendUser->userGroups = $this->groupResolver->resolveGroupsForUser($frontendUser->user, 'fe_groups');
            // let's fake having a user with that groups, too
            // This can be removed once #90989 is fixed
            $frontendUser->user['uid'] = PHP_INT_MAX;
            $groupUids = array_column($frontendUser->userGroups, 'uid');
            $context->setAspect('frontend.user', new UserAspect($frontendUser, array_merge([-2], $groupUids)));
        }
        $isPreview = $simulateUserGroup || $simTime || $showHiddenPages || $showHiddenRecords || $showScheduledRecords;
        if ($context->hasAspect('frontend.preview')) {
            /** @var PreviewAspect $existingPreviewAspect */
            $existingPreviewAspect = $context->getAspect('frontend.preview');
            $isPreview = $existingPreviewAspect->isPreview() || $isPreview;
        }
        $context->setAspect('frontend.preview', new PreviewAspect($isPreview));
    }

    public function getJavaScriptFiles(): array
    {
        return ['EXT:adminpanel/Resources/Public/JavaScript/modules/preview.js'];
    }

    protected function clearPreviewSettings(Context $context): void
    {
        $GLOBALS['SIM_EXEC_TIME'] = $GLOBALS['EXEC_TIME'];
        $GLOBALS['SIM_ACCESS_TIME'] = $GLOBALS['ACCESS_TIME'];
        $context->setAspect('date', new DateTimeAspect(DateTimeFactory::createFromTimestamp($GLOBALS['SIM_EXEC_TIME'])));
        $context->setAspect('visibility', new VisibilityAspect());
    }

    /**
     * Returns a string array with css files that will be rendered after the module
     */
    public function getCssFiles(): array
    {
        return [];
    }

    public function onSubmit(array $configurationToSave, ServerRequestInterface $request): void
    {
        if (!array_key_exists('preview_showFluidDebug', $configurationToSave)) {
            return;
        }

        $currentShowFluidDebug = $this->getConfigOptionForModule('showFluidDebug');

        if ($configurationToSave['preview_showFluidDebug']  === $currentShowFluidDebug) {
            return;
        }

        $pageId = (int)$request->getParsedBody()['TSFE_ADMIN_PANEL']['preview_clearCacheId'];

        try {
            $this->cacheManager->flushCachesInGroupByTag('pages', 'pageId_' . $pageId);
            $this->cacheManager->getCache('fluid_template')->flush();
        } catch (NoSuchCacheException|NoSuchCacheGroupException $exception) {
            if ($this->logger !== null) {
                $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            }
        }
    }
}
