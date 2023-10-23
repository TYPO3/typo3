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

namespace TYPO3\CMS\Reports\Report;

use TYPO3\CMS\Backend\Controller\Event\ModifyGenericBackendMessagesEvent;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

/**
 * Adds a warning message about problems in the current installation to the About module
 *
 * @internal This is a concrete Event Listener implementation and not part of the TYPO3 Core API.
 */
final class WarningsForAboutModule
{
    private string $reportsModuleName = 'system_reports';

    public function __construct(
        private readonly Registry $registry,
        private readonly Context $context
    ) {}

    /**
     * Tries to get the highest severity of the system's status first, if
     * something is found it is assumed that the status update task is set up
     * properly or the status report has been checked manually. We then add
     * a system warning message.
     */
    public function __invoke(ModifyGenericBackendMessagesEvent $event): void
    {
        if (!$this->context->getAspect('backend.user')->isAdmin()) {
            return;
        }
        // Get the highest severity
        $highestSeverity = $this->registry->get('tx_reports', 'status.highestSeverity');
        if ($highestSeverity === null || $highestSeverity <= ContextualFeedbackSeverity::OK->value) {
            return;
        }
        // Display a message that there's something wrong and that
        // the administrator should take a look at the detailed status report
        $event->addMessage(new FlashMessage(sprintf(
            $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_problemNotification'),
            '<a href="#" data-dispatch-action="TYPO3.ModuleMenu.showModule" '
            . 'data-dispatch-args-list="' . $this->reportsModuleName . '">',
            '</a>'
        )));
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
