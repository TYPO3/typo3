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
    public function __construct(
        private readonly ConfigurationService $configurationService,
    ) {}

    public function getIdentifier(): string
    {
        return 'typoscript-waterfall';
    }

    public function getLabel(): string
    {
        return $this->getLanguageService()->sL(
            'LLL:EXT:adminpanel/Resources/Private/Language/locallang_tsdebug.xlf:sub.waterfall.label'
        );
    }

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
                'languageKey' => $this->getBackendUser()->user['lang'] ?? null,
            ]
        );

        return $view->render();
    }

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
                'languageKey' => $this->getBackendUser()->user['lang'] ?? null,
            ]
        );

        return $view->render();
    }

    protected function getConfigurationOption(string $option): bool
    {
        return (bool)$this->configurationService->getConfigurationOption('tsdebug', $option);
    }

    /**
     * Renders the TypoScript log as string
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

    protected function getTimeTracker(): TimeTracker
    {
        return GeneralUtility::makeInstance(TimeTracker::class);
    }
}
