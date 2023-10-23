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

namespace TYPO3\CMS\Scheduler\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\Controller as BackendController;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper;

/**
 * Render 'Setup Check' view.
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[BackendController]
final class SchedulerSetupCheckController
{
    public function __construct(
        private readonly Registry $registry,
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $languageService = $this->getLanguageService();
        $view = $this->moduleTemplateFactory->create($request);
        $view->assign('dateFormat', [
            'day' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] ?? 'd-m-y',
            'time' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'] ?? 'H:i',
        ]);

        // Display information about last automated run, as stored in the system registry.
        $lastRun = $this->registry->get('tx_scheduler', 'lastRun');
        $lastRunMessageLabel = 'msg.noLastRun';
        $lastRunMessageLabelArguments = [];
        $lastRunSeverity = InfoboxViewHelper::STATE_WARNING;
        if (is_array($lastRun)) {
            if (empty($lastRun['end']) || empty($lastRun['start']) || empty($lastRun['type'])) {
                $lastRunMessageLabel = 'msg.incompleteLastRun';
                $lastRunSeverity = InfoboxViewHelper::STATE_WARNING;
            } else {
                $lastRunMessageLabelArguments = [
                    $lastRun['type'] === 'manual'
                        ? $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.manually')
                        : $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.automatically'),
                    date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], $lastRun['start']),
                    date($GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'], $lastRun['start']),
                    date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], $lastRun['end']),
                    date($GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'], $lastRun['end']),
                ];
                $lastRunMessageLabel = 'msg.lastRun';
                $lastRunSeverity = InfoboxViewHelper::STATE_INFO;
            }
        }

        // Information about cli script.
        $script = $this->determineExecutablePath();
        $isExecutableMessageLabel = 'msg.cliScriptNotExecutable';
        $isExecutableSeverity = InfoboxViewHelper::STATE_ERROR;
        $composerMode = !$script && Environment::isComposerMode();
        if (!$composerMode) {
            // Check if CLI script is executable or not. Skip this check if running Windows since executable detection
            // is not reliable on this platform, the script will always appear as *not* executable.
            $isExecutable = Environment::isWindows() ? true : ($script && is_executable($script));
            if ($isExecutable) {
                $isExecutableMessageLabel = 'msg.cliScriptExecutable';
                $isExecutableSeverity = InfoboxViewHelper::STATE_OK;
            }
        }

        $view->assignMultiple([
            'composerMode' => $composerMode,
            'script' => $script,
            'lastRunMessageLabel' => $lastRunMessageLabel,
            'lastRunMessageLabelArguments' => $lastRunMessageLabelArguments,
            'lastRunSeverity' => $lastRunSeverity,
            'isExecutableMessageLabel' => $isExecutableMessageLabel,
            'isExecutableSeverity' => $isExecutableSeverity,
        ]);
        $view->setTitle(
            $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'),
            $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:function.check')
        );
        $view->makeDocHeaderModuleMenu();
        $this->addDocHeaderShortcutButton($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:function.check'));
        return $view->renderResponse('CheckScreen');
    }

    protected function addDocHeaderShortcutButton(ModuleTemplate $moduleTemplate, string $name): void
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('scheduler_availabletasks')
            ->setDisplayName($name);
        $buttonBar->addButton($shortcutButton);
    }

    private function determineExecutablePath(): ?string
    {
        if (!Environment::isComposerMode()) {
            return GeneralUtility::getFileAbsFileName('EXT:core/bin/typo3');
        }
        $composerJsonFile = getenv('TYPO3_PATH_COMPOSER_ROOT') . '/composer.json';
        if (!file_exists($composerJsonFile) || !($jsonContent = file_get_contents($composerJsonFile))) {
            return null;
        }
        $jsonConfig = @json_decode($jsonContent, true);
        if (empty($jsonConfig) || !is_array($jsonConfig)) {
            return null;
        }
        $vendorDir = trim($jsonConfig['config']['vendor-dir'] ?? 'vendor', '/');
        $binDir = trim($jsonConfig['config']['bin-dir'] ?? $vendorDir . '/bin', '/');
        return sprintf('%s/%s/typo3', getenv('TYPO3_PATH_COMPOSER_ROOT'), $binDir);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
