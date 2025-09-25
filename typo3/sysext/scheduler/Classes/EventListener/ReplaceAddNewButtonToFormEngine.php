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

namespace TYPO3\CMS\Scheduler\EventListener;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Listener replaces the "New" button of FormEngine with the button to open the scheduler task wizard
 */
final readonly class ReplaceAddNewButtonToFormEngine
{
    public function __construct(
        private IconFactory $iconFactory,
        private PageRenderer $pageRenderer,
        private UriBuilder $uriBuilder,
    ) {}

    #[AsEventListener]
    public function __invoke(ModifyButtonBarEvent $event): void
    {
        $request = $this->getRequest();

        if (($request->getAttribute('routing')?->getRoute()?->getOptions()['_identifier'] ?? '') !== 'record_edit') {
            return;
        }

        $editConfig = $request->getQueryParams()['edit'] ?? null;
        if (!is_array($editConfig) || $editConfig === [] || count($editConfig) > 1 || key($editConfig) !== 'tx_scheduler_task') {
            return;
        }

        $buttons = $event->getButtons();
        $leftButtons = $buttons['left'] ?? [];

        $this->pageRenderer->loadJavaScriptModule('@typo3/scheduler/new-scheduler-task-wizard-button.js');

        $addTaskUrl = (string)$this->uriBuilder->buildUriFromRoute('ajax_new_scheduler_task_wizard', [
            'returnUrl' => GeneralUtility::sanitizeLocalUrl($request->getQueryParams()['returnUrl'] ?? '') ?: $request->getAttribute('normalizedParams')->getRequestUri(),
        ]);

        $languageService = $this->getLanguageService();
        $newButton = $event->getButtonBar()->makeFullyRenderedButton()->setHtmlSource(
            '<typo3-scheduler-new-task-wizard-button url="' . $addTaskUrl . '" subject="' . htmlspecialchars($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:function.add')) . '">'
            . $this->iconFactory->getIcon('actions-plus', IconSize::SMALL) . htmlspecialchars($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:function.add')) .
            '</typo3-scheduler-new-task-wizard-button>'
        );

        // Find and replace t3js-editform-new button
        // By replacing the existing button we ensure to respect TSconfig and that user has necessary permissions
        foreach ($leftButtons as $groupIndex => $buttonGroup) {
            foreach ($buttonGroup as $buttonIndex => $button) {
                if (method_exists($button, 'getClasses') && str_contains($button->getClasses(), 't3js-editform-new')) {
                    $leftButtons[$groupIndex][$buttonIndex] = $newButton;
                }
            }
        }

        $buttons['left'] = $leftButtons;
        $event->setButtons($buttons);
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    private function getRequest(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }
}
