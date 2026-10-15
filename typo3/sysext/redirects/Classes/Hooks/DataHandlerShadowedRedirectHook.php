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
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Redirects\Service\IntegrityService;

/**
 * Updates the shadowed integrity status of redirects sharing the source host of
 * a changed redirect, and warns if the changed redirect is involved.
 *
 * @internal
 */
#[Autoconfigure(public: true)]
final readonly class DataHandlerShadowedRedirectHook
{
    public function __construct(
        private IntegrityService $integrityService,
        private ConnectionPool $connectionPool,
        private FlashMessageService $flashMessageService,
    ) {}

    public function processDatamap_afterAllOperations(DataHandler $dataHandler): void
    {
        $uids = [];
        foreach (array_keys($dataHandler->datamap['sys_redirect'] ?? []) as $id) {
            $uid = MathUtility::canBeInterpretedAsInteger($id) ? (int)$id : (int)($dataHandler->substNEWwithIDs[$id] ?? 0);
            if ($uid > 0) {
                $uids[] = $uid;
            }
        }
        $this->updateShadowedRedirects($uids, $dataHandler);
    }

    public function processCmdmap_afterFinish(DataHandler $dataHandler): void
    {
        $this->updateShadowedRedirects(array_map(intval(...), array_keys($dataHandler->cmdmap['sys_redirect'] ?? [])), $dataHandler);
    }

    /**
     * @param int[] $uids
     */
    private function updateShadowedRedirects(array $uids, DataHandler $dataHandler): void
    {
        if ($uids === []) {
            return;
        }
        $sourceHosts = $this->getSourceHosts($uids);
        foreach ($uids as $uid) {
            $previousSourceHost = $dataHandler->getHistoryRecords()['sys_redirect:' . $uid]['oldRecord']['source_host'] ?? null;
            if ($previousSourceHost !== null) {
                $sourceHosts[] = (string)$previousSourceHost;
            }
        }
        // An empty source host is treated like "*" when matching redirects.
        if (array_intersect($sourceHosts, ['', '*']) !== []) {
            $sourceHosts = array_merge($sourceHosts, ['', '*']);
        }

        $shadowedRedirects = $this->integrityService->updateShadowedIntegrityStatus(array_values(array_unique($sourceHosts)));
        $languageService = $GLOBALS['LANG'] ?? null;
        if (!$languageService instanceof LanguageService) {
            return;
        }
        $reportedUids = [];
        foreach ($shadowedRedirects as $shadowedRedirect) {
            $uid = $shadowedRedirect['redirect']['uid'];
            $shadowedBy = $shadowedRedirect['redirect']['shadowed_by'];
            if (isset($reportedUids[$uid]) || (!in_array($uid, $uids, true) && !in_array($shadowedBy, $uids, true))) {
                continue;
            }
            $reportedUids[$uid] = true;
            $this->flashMessageService->getMessageQueueByIdentifier()->enqueue(new FlashMessage(
                sprintf($languageService->sL('redirects.module_redirect:shadowed_redirect.message'), $uid, $shadowedBy),
                $languageService->sL('redirects.module_redirect:shadowed_redirect.title'),
                ContextualFeedbackSeverity::WARNING,
                true
            ));
        }
    }

    /**
     * @param int[] $uids
     * @return string[]
     */
    private function getSourceHosts(array $uids): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_redirect');
        $queryBuilder->getRestrictions()->removeAll();
        return array_map(strval(...), $queryBuilder
            ->select('source_host')
            ->from('sys_redirect')
            ->where(
                $queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter($uids, Connection::PARAM_INT_ARRAY))
            )
            ->executeQuery()
            ->fetchFirstColumn());
    }
}
