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

namespace TYPO3\CMS\Redirects\Hooks;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Redirects\Service\ShortUrlService;

/**
 * Initially set values for sys_redirects of type "short_url"
 *
 * @internal
 */
#[Autoconfigure(public: true)]
final readonly class HandleNewShortUrlRecord
{
    public function __construct(
        private ShortUrlService $shortUrlService,
        private FlashMessageService $flashMessageService,
    ) {}

    public function processDatamap_preProcessFieldArray(
        ?array &$incomingFieldArray,
        string $table,
        int|string $id,
        DataHandler $dataHandler
    ): void {
        if ($table !== 'sys_redirect'
            || MathUtility::canBeInterpretedAsInteger($id)
            || ($incomingFieldArray['redirect_type'] ?? '') !== 'short_url'
        ) {
            return;
        }

        // Set defaults when creating a new Short URL
        $incomingFieldArray['keep_query_parameters'] = 1;
        $incomingFieldArray['protected'] = 1;
        $incomingFieldArray['is_regexp'] = 0;
        $incomingFieldArray['disabled'] = 0;
        // Prevent saving the record if source_path is empty
        if (empty($incomingFieldArray['source_path'])) {
            $incomingFieldArray = null;
            return;
        }
        // Add '/' at the beginning of the source_path if not present
        $incomingFieldArray['source_path'] = $incomingFieldArray['source_path'][0] === '/' ? $incomingFieldArray['source_path'] : '/' . $incomingFieldArray['source_path'];
        // Check that the short URL does not already exist
        if (!$this->shortUrlService->isUniqueShortUrl($incomingFieldArray['source_host'], $incomingFieldArray['source_path'])) {
            $incomingFieldArray = null;
            $message = $this->getLanguageService()->sL('redirects.modules.short_urls:validation.duplicate_short_url');
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $message,
                '',
                ContextualFeedbackSeverity::ERROR,
                true
            );
            $defaultFlashMessageQueue = $this->flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
