<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Adminpanel\Modules;

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
use TYPO3\CMS\Adminpanel\Repositories\FrontendGroupsRepository;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Admin Panel Preview Module
 */
class PreviewModule extends AbstractModule
{
    /**
     * module configuration, set on initialize
     *
     * @var array
     */
    protected $config;

    /**
     * @inheritdoc
     */
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
            'LLL:' . $this->extResources . '/Language/locallang_preview.xlf:module.label'
        );
    }

    /**
     * @inheritdoc
     */
    public function initializeModule(ServerRequestInterface $request): void
    {
        $this->config = [
            'showHiddenPages' => (bool)$this->getConfigOptionForModule('showHiddenPages'),
            'simulateDate' => $this->getConfigOptionForModule('simulateDate'),
            'showHiddenRecords' => (bool)$this->getConfigOptionForModule('showHiddenRecords'),
            'simulateUserGroup' => $this->getConfigOptionForModule('simulateUserGroup'),
            'showFluidDebug' => (bool)$this->getConfigOptionForModule('showFluidDebug'),
        ];
        $this->initializeFrontendPreview(
            $this->config['showHiddenPages'],
            $this->config['showHiddenRecords'],
            $this->config['simulateDate'],
            $this->config['simulateUserGroup']
        );
    }

    /**
     * @inheritdoc
     */
    public function getSettings(): string
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templateNameAndPath = $this->extResources . '/Templates/Modules/Settings/Preview.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndPath));
        $view->setPartialRootPaths([$this->extResources . '/Partials']);

        $frontendGroupsRepository = GeneralUtility::makeInstance(FrontendGroupsRepository::class);

        $view->assignMultiple(
            [
                'show' => [
                    'pageId' => (int)$this->getTypoScriptFrontendController()->id,
                    'hiddenPages' => $this->config['showHiddenPages'],
                    'hiddenRecords' => $this->config['showHiddenRecords'],
                    'fluidDebug' => $this->config['showFluidDebug'],
                ],
                'simulateDate' => $this->config['simulateDate'],
                'frontendUserGroups' => [
                    'availableGroups' => $frontendGroupsRepository->getAvailableFrontendUserGroups(),
                    'selected' => $this->config['simulateUserGroup'],
                ],
            ]
        );
        return $view->render();
    }

    /**
     * Clear page cache if fluid debug output is enabled
     *
     * @param array $input
     * @param ServerRequestInterface $request
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     */
    public function onSubmit(array $input, ServerRequestInterface $request): void
    {
        $activeConfiguration = (int)$this->getConfigOptionForModule('showFluidDebug');
        if (isset($input['preview_showFluidDebug']) && (int)$input['preview_showFluidDebug'] !== $activeConfiguration) {
            $pageId = (int)$request->getParsedBody()['TSFE_ADMIN_PANEL']['preview_clearCacheId'];
            $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_pages');
            $cache->flushByTag('pageId_' . $pageId);
        }
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
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * Initialize frontend preview functionality incl.
     * simulation of users or time
     *
     * @param bool $showHiddenPages
     * @param bool $showHiddenRecords
     * @param string $simulateDate
     * @param string $simulateUserGroup
     */
    protected function initializeFrontendPreview(
        bool $showHiddenPages,
        bool $showHiddenRecords,
        string $simulateDate,
        string $simulateUserGroup
    ): void {
        $context = GeneralUtility::makeInstance(Context::class);
        $tsfe = $this->getTypoScriptFrontendController();
        $tsfe->clear_preview();
        $tsfe->fePreview = 1;
        // Simulate date
        $simTime = null;
        if ($simulateDate) {
            $simTime = $this->parseDate($simulateDate);
        }
        if ($simTime) {
            $GLOBALS['SIM_EXEC_TIME'] = $simTime;
            $GLOBALS['SIM_ACCESS_TIME'] = $simTime - $simTime % 60;
            $context->setAspect('date', GeneralUtility::makeInstance(DateTimeAspect::class, new \DateTimeImmutable('@' . $GLOBALS['SIM_EXEC_TIME'])));
        }
        $context->setAspect('visibility', GeneralUtility::makeInstance(VisibilityAspect::class, $showHiddenPages, $showHiddenRecords));
        // simulate user
        $tsfe->simUserGroup = $simulateUserGroup;
        if ($tsfe->simUserGroup) {
            if ($tsfe->fe_user->user) {
                $tsfe->fe_user->user[$tsfe->fe_user->usergroup_column] = $tsfe->simUserGroup;
            } else {
                $tsfe->fe_user = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
                $tsfe->fe_user->user = [
                    $tsfe->fe_user->usergroup_column => $tsfe->simUserGroup,
                ];
            }
            $context->setAspect('frontend.user', GeneralUtility::makeInstance(UserAspect::class, $tsfe->fe_user ?: null));
        }
        if (!$tsfe->simUserGroup && !$simTime && !$showHiddenPages && !$showHiddenRecords) {
            $tsfe->fePreview = 0;
        }
    }

    /**
     * @return array
     */
    public function getJavaScriptFiles(): array
    {
        return ['EXT:adminpanel/Resources/Public/JavaScript/Modules/Preview.js'];
    }

    /**
     * @param string $simulateDate
     * @return int
     */
    protected function parseDate(string $simulateDate): ?int
    {
        $date = false;
        try {
            $date = new \DateTime($simulateDate);
        } catch (\Exception $e) {
            if (is_numeric($simulateDate)) {
                try {
                    $date = new \DateTime('@' . $simulateDate);
                } catch (\Exception $e) {
                    $date = false;
                }
            }
        }
        if ($date !== false) {
            $simTime = $date->getTimestamp();
        }
        return $simTime ?? null;
    }
}
