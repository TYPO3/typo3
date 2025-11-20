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

namespace TYPO3\CMS\Core\Command\Output;

use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

class MessageRenderer
{
    public function renderAll(FlashMessageQueue $queue, OutputInterface $output): void
    {
        foreach ($queue->getAllMessages() as $message) {
            $this->renderOne($message, $output);
        }
    }

    public function renderOne(FlashMessage $message, OutputInterface $output): void
    {
        [$style, $verbosity] = match ($message->getSeverity()) {
            ContextualFeedbackSeverity::INFO,
            ContextualFeedbackSeverity::NOTICE,
            ContextualFeedbackSeverity::OK => ['info', $output::VERBOSITY_VERBOSE],
            ContextualFeedbackSeverity::WARNING => ['comment', $output::VERBOSITY_NORMAL],
            ContextualFeedbackSeverity::ERROR => ['error', $output::VERBOSITY_NORMAL],
        };
        $formattedMessage = sprintf(
            "<%s><bold>%s</bold>\n%s</%s>\n",
            $style,
            $message->getTitle(),
            $message->getMessage(),
            $style,
        );
        $output->writeln($formattedMessage, $verbosity);
    }
}
