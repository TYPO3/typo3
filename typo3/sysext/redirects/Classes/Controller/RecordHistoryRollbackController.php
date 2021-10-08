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

namespace TYPO3\CMS\Redirects\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\History\RecordHistory;
use TYPO3\CMS\Backend\History\RecordHistoryRollback;
use TYPO3\CMS\Core\DataHandling\Model\CorrelationId;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Redirects\Service\SlugService;

/**
 * @internal
 */
class RecordHistoryRollbackController
{
    protected ?LanguageService $languageService = null;
    protected LanguageServiceFactory $languageServiceFactory;

    public function __construct(LanguageServiceFactory $languageServiceFactory)
    {
        $this->languageServiceFactory = $languageServiceFactory;
    }

    public function revertCorrelation(ServerRequestInterface $request): ResponseInterface
    {
        $this->languageService = $this->languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER']);
        $revertedCorrelationTypes = [];
        $correlationIds = $request->getQueryParams()['correlation_ids'] ?? [];
        /** @var CorrelationId[] $correlationIds */
        $correlationIds = array_map(
            static function (string $correlationId) {
                return CorrelationId::fromString($correlationId);
            },
            $correlationIds
        );
        foreach ($correlationIds as $correlationId) {
            $aspects = $correlationId->getAspects();
            if (count($aspects) < 2 || $aspects[0] !== SlugService::CORRELATION_ID_IDENTIFIER) {
                continue;
            }
            $revertedCorrelationTypes[] = $correlationId->getAspects()[1];
            $this->rollBackCorrelation($correlationId);
        }
        $result = [
            'status' => 'error',
            'title' => $this->languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang_slug_service.xlf:redirects_error_title'),
            'message' => $this->languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang_slug_service.xlf:redirects_error_message'),
        ];
        if (in_array('redirect', $revertedCorrelationTypes, true)) {
            $result = [
                'status' => 'ok',
                'title' => $this->languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang_slug_service.xlf:revert_redirects_success_title'),
                'message' => $this->languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang_slug_service.xlf:revert_redirects_success_message'),
            ];
            if (in_array('slug', $revertedCorrelationTypes, true)) {
                $result = [
                    'status' => 'ok',
                    'title' => $this->languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang_slug_service.xlf:revert_update_success_title'),
                    'message' => $this->languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang_slug_service.xlf:revert_update_success_message'),
                ];
            }
        }
        return new JsonResponse($result);
    }

    protected function rollBackCorrelation(CorrelationId $correlationId): void
    {
        $recordHistoryRollback = GeneralUtility::makeInstance(RecordHistoryRollback::class);
        foreach (GeneralUtility::makeInstance(RecordHistory::class)->findEventsForCorrelation((string)$correlationId) as $recordHistoryEntry) {
            $element = $recordHistoryEntry['tablename'] . ':' . $recordHistoryEntry['recuid'];
            $tempRecordHistory = GeneralUtility::makeInstance(RecordHistory::class, $element);
            $tempRecordHistory->setLastHistoryEntryNumber((int)$recordHistoryEntry['uid']);
            $recordHistoryRollback->performRollback('ALL', $tempRecordHistory->getDiff($tempRecordHistory->getChangeLog()));
        }
    }
}
