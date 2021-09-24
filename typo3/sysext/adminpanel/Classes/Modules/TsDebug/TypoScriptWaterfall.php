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

namespace TYPO3\CMS\Adminpanel\Modules\TsDebug;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractSubModule;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleData;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleSettingsProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\RequestEnricherInterface;
use TYPO3\CMS\Adminpanel\Service\ConfigurationService;
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\TypoScriptAspect;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class TypoScriptWaterfall
 *
 * @internal
 */
class TypoScriptWaterfall extends AbstractSubModule implements RequestEnricherInterface, ModuleSettingsProviderInterface
{
    /**
     * @var ConfigurationService
     */
    protected $configurationService;

    public function __construct()
    {
        $this->configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
    }

    /**
     * @inheritdoc
     */
    public function getIdentifier(): string
    {
        return 'typoscript-waterfall';
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return $this->getLanguageService()->sL(
            'LLL:EXT:adminpanel/Resources/Private/Language/locallang_tsdebug.xlf:sub.waterfall.label'
        );
    }

    /**
     * @inheritdoc
     */
    public function enrich(ServerRequestInterface $request): ServerRequestInterface
    {
        if ($this->getConfigurationOption('forceTemplateParsing')) {
            GeneralUtility::makeInstance(Context::class)->setAspect('typoscript', GeneralUtility::makeInstance(TypoScriptAspect::class, true));
            $request = $request->withAttribute('noCache', true);
        }
        $this->getTimeTracker()->LR = (bool)$this->getConfigurationOption('LR');
        return $request;
    }

    /**
     * Creates the content for the "tsdebug" section ("module") of the Admin Panel
     *
     * @param ModuleData $data
     * @return string HTML
     */
    public function getContent(ModuleData $data): string
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templateNameAndPath = 'EXT:adminpanel/Resources/Private/Templates/Modules/TsDebug/TypoScript.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndPath));
        $view->setPartialRootPaths(['EXT:adminpanel/Resources/Private/Partials']);

        $view->assignMultiple(
            [
                'tree' => (int)$this->getConfigurationOption('tree'),
                'display' => [
                    'times' => (int)$this->getConfigurationOption('displayTimes'),
                    'messages' => (int)$this->getConfigurationOption('displayMessages'),
                    'content' => (int)$this->getConfigurationOption('displayContent'),
                ],
                'trackContentRendering' => (int)$this->getConfigurationOption('LR'),
                'forceTemplateParsing' => (int)$this->getConfigurationOption('forceTemplateParsing'),
                'typoScriptLog' => $this->renderTypoScriptLog(),
            ]
        );

        return $view->render();
    }

    /**
     * @inheritdoc
     */
    public function getSettings(): string
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templateNameAndPath = 'EXT:adminpanel/Resources/Private/Templates/Modules/TsDebug/TypoScriptSettings.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndPath));
        $view->setPartialRootPaths(['EXT:adminpanel/Resources/Private/Partials']);

        $view->assignMultiple(
            [
                'tree' => (int)$this->getConfigurationOption('tree'),
                'display' => [
                    'times' => (int)$this->getConfigurationOption('displayTimes'),
                    'messages' => (int)$this->getConfigurationOption('displayMessages'),
                    'content' => (int)$this->getConfigurationOption('displayContent'),
                ],
                'trackContentRendering' => (int)$this->getConfigurationOption('LR'),
                'forceTemplateParsing' => (int)$this->getConfigurationOption('forceTemplateParsing'),
            ]
        );

        return $view->render();
    }

    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication|FrontendBackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @param string $option
     * @return bool
     */
    protected function getConfigurationOption(string $option): bool
    {
        return (bool)$this->configurationService->getConfigurationOption('tsdebug', $option);
    }

    /**
     * Renders the TypoScript log as string
     *
     * @return string
     */
    protected function renderTypoScriptLog(): string
    {
        $timeTracker = $this->getTimeTracker();
        $timeTracker->printConf['flag_tree'] = $this->getConfigurationOption('tree');
        $timeTracker->printConf['allTime'] = $this->getConfigurationOption(
            'displayTimes'
        );
        $timeTracker->printConf['flag_messages'] = $this->getConfigurationOption(
            'displayMessages'
        );
        $timeTracker->printConf['flag_content'] = $this->getConfigurationOption(
            'displayContent'
        );
        return $timeTracker->printTSlog();
    }

    /**
     * @return TimeTracker
     */
    protected function getTimeTracker(): TimeTracker
    {
        return GeneralUtility::makeInstance(TimeTracker::class);
    }
}
