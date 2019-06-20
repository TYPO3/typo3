<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Adminpanel\Modules\TsDebug;

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
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractSubModule;
use TYPO3\CMS\Adminpanel\ModuleApi\InitializableInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleData;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleSettingsProviderInterface;
use TYPO3\CMS\Adminpanel\Service\ConfigurationService;
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class TypoScriptWaterfall
 *
 * @internal
 */
class TypoScriptWaterfall extends AbstractSubModule implements InitializableInterface, ModuleSettingsProviderInterface
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
    public function initializeModule(ServerRequestInterface $request): void
    {
        $typoScriptFrontend = $this->getTypoScriptFrontendController();
        $typoScriptFrontend->forceTemplateParsing = $this->getConfigurationOption(
            'forceTemplateParsing'
        );
        if ($typoScriptFrontend->forceTemplateParsing) {
            $typoScriptFrontend->set_no_cache('Admin Panel: Force template parsing', true);
        }
        $this->getTimeTracker()->LR = (bool)$this->getConfigurationOption('LR');
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
                'forceTemplateParsing' => (int)$this->getConfigurationOption('forceTemplateParsing')
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

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }
}
