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

namespace TYPO3\CMS\Scheduler;

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Base class for providers of additional fields
 */
abstract class AbstractAdditionalFieldProvider implements AdditionalFieldProviderInterface
{
    /**
     * Add a flash message
     *
     * @param string $message the flash message content
     * @param value-of<ContextualFeedbackSeverity>|ContextualFeedbackSeverity $severity the flash message severity
     *
     * @todo: Change $severity to allow ContextualFeedbackSeverity only in v13
     */
    protected function addMessage(string $message, int|ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::OK): void
    {
        if (is_int($severity)) {
            // @deprecated int type for $severity deprecated in v12, will change to Severity only in v13.
            $severity = ContextualFeedbackSeverity::transform($severity);
        }
        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, '', $severity);
        $service = GeneralUtility::makeInstance(FlashMessageService::class);
        $queue = $service->getMessageQueueByIdentifier();
        $queue->enqueue($flashMessage);
    }
}
