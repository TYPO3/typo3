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
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractModule;
use TYPO3\CMS\Adminpanel\ModuleApi\InitializableInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\PageSettingsProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ResourceProviderInterface;
use TYPO3\CMS\Adminpanel\Repositories\FrontendGroupsRepository;
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
class PreviewModule extends AbstractModule implements InitializableInterface, PageSettingsProviderInterface, ResourceProviderInterface
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
            'LLL:EXT:adminpanel/Resources/Private/Language/locallang_preview.xlf:module.label'
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
            'simulateUserGroup' => (int)$this->getConfigOptionForModule('simulateUserGroup'),
            'showFluidDebug' => (bool)$this->getConfigOptionForModule('showFluidDebug'),
        ];
        if ($this->config['showFluidDebug']) {
            // forcibly unset fluid caching as it does not care about the tsfe based caching settings
            unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['fluid_template']['frontend']);
            $GLOBALS['TSFE']->set_no_cache('Cache is disabled if fluid debugging is enabled', true);
        }
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
    public function getPageSettings(): string
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templateNameAndPath = 'EXT:adminpanel/Resources/Private/Templates/Modules/Settings/Preview.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndPath));
        $view->setPartialRootPaths(['EXT:adminpanel/Resources/Private/Partials']);

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
                    'selected' => (int)$this->config['simulateUserGroup'],
                ],
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
     * @param int $simulateUserGroup UID of the fe_group to simulate
     */
    protected function initializeFrontendPreview(
        bool $showHiddenPages,
        bool $showHiddenRecords,
        string $simulateDate,
        int $simulateUserGroup
    ): void {
        $context = GeneralUtility::makeInstance(Context::class);
        $typoScriptFrontendController = $this->getTypoScriptFrontendController();
        $typoScriptFrontendController->clear_preview();
        $typoScriptFrontendController->fePreview = 1;

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
            $typoScriptFrontendController->simUserGroup = $simulateUserGroup;
            if (!$typoScriptFrontendController->fe_user instanceof FrontendUserAuthentication) {
                $typoScriptFrontendController->fe_user = GeneralUtility::makeInstance(
                    FrontendUserAuthentication::class
                );
            }
            if (!is_array($typoScriptFrontendController->fe_user->user)) {
                $typoScriptFrontendController->fe_user->user = [];
            }
            $typoScriptFrontendController->fe_user->user[$typoScriptFrontendController->fe_user->usergroup_column] = $simulateUserGroup;
            $context->setAspect(
                'frontend.user',
                GeneralUtility::makeInstance(
                    UserAspect::class,
                    $typoScriptFrontendController->fe_user ?: null,
                    [$simulateUserGroup]
                )
            );
        }
        if (!$simulateUserGroup && !$simTime && !$showHiddenPages && !$showHiddenRecords) {
            $typoScriptFrontendController->fePreview = 0;
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
