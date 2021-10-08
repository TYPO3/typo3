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

namespace TYPO3\CMS\Backend\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Render FlashMessages
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class FlashMessageController
{
    /**
     * Renders the FlashMessages from queue and returns them as JSON.
     *
     * @return ResponseInterface
     */
    public function getQueuedFlashMessagesAction(): ResponseInterface
    {
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $flashMessages = $defaultFlashMessageQueue->getAllMessagesAndFlush();

        $messages = [];
        foreach ($flashMessages as $flashMessage) {
            $messages[] = [
                'title' => $flashMessage->getTitle(),
                'message' => $flashMessage->getMessage(),
                'severity' => $flashMessage->getSeverity(),
            ];
        }

        return new JsonResponse($messages);
    }
}
