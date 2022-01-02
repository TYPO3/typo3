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
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractModule;
use TYPO3\CMS\Adminpanel\ModuleApi\PageSettingsProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\RequestEnricherInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ResourceProviderInterface;
use TYPO3\CMS\Adminpanel\Repositories\FrontendGroupsRepository;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\Aspect\PreviewAspect;

/**
 * Admin Panel Preview Module
 */
class PreviewModule extends AbstractModule implements RequestEnricherInterface, PageSettingsProviderInterface, ResourceProviderInterface
{
    /**
     * module configuration, set on initialize
     *
     * @var array
     */
    protected $config;

    public function getIconIdentifier(): string
    {
        return 'actions-preview';
    }

    /**
     * @inheritdoc
     */
    public function getIdentifier(): string
    {
        return 'preview';
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return $this->getLanguageService()->sL(
            'LLL:EXT:adminpanel/Resources/Private/Language/locallang_preview.xlf:module.label'
        );
    }

    /**
     * @inheritdoc
     */
    public function enrich(ServerRequestInterface $request): ServerRequestInterface
    {
        // Backend preview params (ADMCMD_) take precedence over configured admin panel values
        $simulateGroupByRequest = (int)($request->getQueryParams()['ADMCMD_simUser'] ?? 0);
        $simulateTimeByRequest = (int)($request->getQueryParams()['ADMCMD_simTime'] ?? 0);
        $this->config = [
            'showHiddenPages' => (bool)$this->getConfigOptionForModule('showHiddenPages'),
            'simulateDate' => $simulateTimeByRequest ?: (int)$this->getConfigOptionForModule('simulateDate'),
            'showHiddenRecords' => (bool)$this->getConfigOptionForModule('showHiddenRecords'),
            'simulateUserGroup' => $simulateGroupByRequest ?: (int)$this->getConfigOptionForModule('simulateUserGroup'),
            'showFluidDebug' => (bool)$this->getConfigOptionForModule('showFluidDebug'),
        ];
        if ($this->config['showFluidDebug']) {
            // forcibly unset fluid caching as it does not care about the tsfe based caching settings
            unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['fluid_template']['frontend']);
            $request = $request->withAttribute('noCache', true);
        }
        $this->initializeFrontendPreview(
            $this->config['showHiddenPages'],
            $this->config['showHiddenRecords'],
            $this->config['simulateDate'],
            $this->config['simulateUserGroup'],
            $request
        );

        return $request;
    }

    /**
     * @inheritdoc
     */
    public function getPageSettings(): string
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templateNameAndPath = 'EXT:adminpanel/Resources/Private/Templates/Modules/Settings/Preview.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndPath));
        $view->setPartialRootPaths(['EXT:adminpanel/Resources/Private/Partials']);

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
                    'hiddenPages' => $this->config['showHiddenPages'],
                    'hiddenRecords' => $this->config['showHiddenRecords'],
                    'fluidDebug' => $this->config['showFluidDebug'],
                ],
                'simulateDate' => $this->config['simulateDate'],
                'frontendUserGroups' => [
                    'availableGroups' => $frontendGroupsRepository->getAvailableFrontendUserGroups(),
                    'selected' => (int)$this->config['simulateUserGroup'],
                ],
                'languageKey' => $this->getBackendUser()->user['lang'],
            ]
        );
        return $view->render();
    }

    /**
     * @param string $option
     * @return string
     */
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
     * @param bool $showHiddenPages
     * @param bool $showHiddenRecords
     * @param int $simulateDate
     * @param int $simulateUserGroup UID of the fe_group to simulate
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @throws \Exception
     */
    protected function initializeFrontendPreview(
        bool $showHiddenPages,
        bool $showHiddenRecords,
        int $simulateDate,
        int $simulateUserGroup,
        ServerRequestInterface $request
    ): void {
        $context = GeneralUtility::makeInstance(Context::class);
        $this->clearPreviewSettings($context);

        // Modify visibility settings (hidden pages + hidden content)
        $context->setAspect(
            'visibility',
            GeneralUtility::makeInstance(VisibilityAspect::class, $showHiddenPages, $showHiddenRecords)
        );

        // Simulate date
        $simTime = null;
        if ($simulateDate) {
            $simTime = $this->parseDate($simulateDate);
            if ($simTime) {
                $GLOBALS['SIM_EXEC_TIME'] = $simTime;
                $GLOBALS['SIM_ACCESS_TIME'] = $simTime - $simTime % 60;
                $context->setAspect(
                    'date',
                    GeneralUtility::makeInstance(
                        DateTimeAspect::class,
                        new \DateTimeImmutable('@' . $GLOBALS['SIM_EXEC_TIME'])
                    )
                );
            }
        }
        // simulate usergroup
        if ($simulateUserGroup) {
            $frontendUser = $request->getAttribute('frontend.user');
            $frontendUser->user[$frontendUser->usergroup_column] = $simulateUserGroup;
            // let's fake having a user with that group, too
            // This can be removed once #90989 is fixed
            $frontendUser->user['uid'] = PHP_INT_MAX;
            $context->setAspect(
                'frontend.user',
                GeneralUtility::makeInstance(
                    UserAspect::class,
                    $frontendUser,
                    [$simulateUserGroup]
                )
            );
        }
        $isPreview = $simulateUserGroup || $simTime || $showHiddenPages || $showHiddenRecords;
        if ($context->hasAspect('frontend.preview')) {
            $existingPreviewAspect = $context->getAspect('frontend.preview');
            $isPreview = $existingPreviewAspect->isPreview() || $isPreview;
        }
        $previewAspect = GeneralUtility::makeInstance(PreviewAspect::class, $isPreview);
        $context->setAspect('frontend.preview', $previewAspect);
    }

    /**
     * @return array
     */
    public function getJavaScriptFiles(): array
    {
        return ['EXT:adminpanel/Resources/Public/JavaScript/Modules/Preview.js'];
    }

    /**
     * The simulated date needs to be a timestring (UTC)
     *
     * Simulation date is either set via configuration of AdminPanel (Date and Time Fields) or via ADMCMD_ $_GET
     * parameter from backend previews
     *
     * @param int $simulateDate
     * @return int
     */
    protected function parseDate(int $simulateDate): ?int
    {
        try {
            $simTime = (new \DateTime('@' . $simulateDate))->getTimestamp();
            $simTime = max($simTime, 60);
        } catch (\Exception $e) {
            $simTime = null;
        }
        return $simTime;
    }

    protected function clearPreviewSettings(Context $context): void
    {
        $GLOBALS['SIM_EXEC_TIME'] = $GLOBALS['EXEC_TIME'];
        $GLOBALS['SIM_ACCESS_TIME'] = $GLOBALS['ACCESS_TIME'];
        $context->setAspect('date', GeneralUtility::makeInstance(DateTimeAspect::class, new \DateTimeImmutable('@' . $GLOBALS['SIM_EXEC_TIME'])));
        $context->setAspect('visibility', GeneralUtility::makeInstance(VisibilityAspect::class));
    }

    /**
     * Returns a string array with css files that will be rendered after the module
     *
     * @return array
     */
    public function getCssFiles(): array
    {
        return [];
    }
}
