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

namespace TYPO3\CMS\Backend\Security\ContentSecurityPolicy;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Uid\UuidV4;
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Event\InvestigateMutationsEvent;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ModelService;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationSuggestion;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\PolicyProvider;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\Report;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\ReportAttribute;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\ReportDemand;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\ReportRepository;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\ReportStatus;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\Resolution;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\ResolutionRepository;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\SummarizedReport;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * AJAX endpoint for the CSP backend module, providing access to persisted CSP reports & resolutions.
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
#[Controller]
class CspAjaxController
{
    public function __construct(
        protected readonly ModelService $modelService,
        protected readonly PolicyProvider $policyProvider,
        protected readonly ReportRepository $reportRepository,
        protected readonly ResolutionRepository $resolutionRepository,
        protected readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isSystemMaintainer()) {
            return (new NullResponse())->withStatus(403);
        }
        return $this->dispatchAction($request)
            ?? (new NullResponse())->withStatus(500);
    }

    protected function dispatchAction(ServerRequestInterface $request): ?ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $hmac = $parsedBody['hmac'] ?? null;
        $action = $parsedBody['action'] ?? null;
        $summaries = $parsedBody['summaries'] ?? [];
        $scope = Scope::tryFrom($parsedBody['scope'] ?? '');
        $uuid = $parsedBody['uuid'] ?? null;
        if ($uuid !== null) {
            $uuid = Uuidv4::fromString($uuid);
        }
        if (!empty($parsedBody['suggestion'])) {
            $suggestion = $this->modelService->buildMutationSuggestionFromArray($parsedBody['suggestion']);
        }
        // reports
        if ($action === 'fetchReports') {
            return $this->fetchReportsAction($scope);
        }
        if ($action === 'muteReport' && is_array($summaries)) {
            return $this->muteReportAction(...$summaries);
        }
        if ($action === 'deleteReport' && is_array($summaries)) {
            return $this->deleteReportAction(...$summaries);
        }
        if ($action === 'deleteReports') {
            return $this->deleteReportsAction($scope);
        }
        if ($action === 'handleReport' && $uuid !== null) {
            return $this->handleReportAction($uuid);
        }
        if ($action === 'mutateReport'
            && $scope !== null
            && is_array($summaries)
            && isset($suggestion)
            && hash_equals($suggestion->hmac(), $hmac)
        ) {
            return $this->mutateReportAction($scope, $suggestion, ...$summaries);
        }
        return null;
    }

    protected function fetchReportsAction(?Scope $scope): ResponseInterface
    {
        $demand = ReportDemand::create();
        $demand->scope = $scope;
        $reports = $this->reportRepository->findAllSummarized($demand);
        // @todo not sure whether this is a good idea performance-wise
        $reports = array_map(
            function (SummarizedReport $report): SummarizedReport {
                $event = $this->dispatchInvestigateMutationsEvent($report);
                if ($event->getMutationSuggestions() !== []) {
                    $mutationHashes = array_map(
                        static fn(MutationSuggestion $suggestion): string => $suggestion->hash(),
                        $event->getMutationSuggestions()
                    );
                    $report = $report->withMutationHashes(...$mutationHashes)
                        ->withAttribute(ReportAttribute::fixable);
                }
                return $report;
            },
            $reports
        );
        return new JsonResponse($reports);
    }

    protected function muteReportAction(string ...$summaries): ResponseInterface
    {
        $reports = $this->reportRepository->findBySummary(...$summaries);
        $uuids = array_map(static fn(Report $report) => $report->uuid, $reports);
        $this->reportRepository->updateStatus(ReportStatus::Muted, ...$uuids);
        return new JsonResponse(['uuids' => $uuids]);
    }

    protected function deleteReportAction(string ...$summaries): ResponseInterface
    {
        $reports = $this->reportRepository->findBySummary(...$summaries);
        $reportUuids = $this->resolveReportUuids(...$reports);
        $this->reportRepository->updateStatus(ReportStatus::Deleted, ...$reportUuids);
        return new JsonResponse(['uuids' => $reportUuids]);
    }

    protected function deleteReportsAction(?Scope $scope): ResponseInterface
    {
        $amount = $this->reportRepository->removeAll($scope);
        return new JsonResponse(['amount' => $amount]);
    }

    protected function handleReportAction(UuidV4 $uuid): ResponseInterface
    {
        $report = $this->reportRepository->findByUuid($uuid);
        if ($report === null) {
            return new JsonResponse();
        }
        $event = $this->dispatchInvestigateMutationsEvent($report);
        $suggestions = $event->getMutationSuggestions();
        // reverse sort by priority (higher priorities take precedence)
        usort($suggestions, static fn(MutationSuggestion $a, MutationSuggestion $b) => $b->priority <=> $a->priority);
        return new JsonResponse(array_values($suggestions));
    }

    protected function mutateReportAction(Scope $scope, MutationSuggestion $suggestion, string ...$initiators): ResponseInterface
    {
        $summary = $this->generateResolutionSummary($scope, $suggestion);
        $resolution = $this->resolutionRepository->findBySummary($summary);
        $reports = $this->reportRepository->findBySummary(...$initiators);
        if ($resolution !== null || $reports === []) {
            return new JsonResponse();
        }
        $resolution = new Resolution($summary, $scope, $suggestion->identifier, $suggestion->collection, ['initiators' => $initiators]);
        $this->resolutionRepository->add($resolution);
        $reportUuids = $this->resolveReportUuids(...$reports);
        $this->reportRepository->updateStatus(ReportStatus::Handled, ...$reportUuids);
        return new JsonResponse(['initiators' => $initiators, 'uuids' => $reportUuids]);
    }

    protected function dispatchInvestigateMutationsEvent(Report $report): InvestigateMutationsEvent
    {
        $policy = $this->policyProvider->provideFor($report->scope);
        $event = new InvestigateMutationsEvent($policy, $report);
        $this->eventDispatcher->dispatch($event);
        return $event;
    }

    protected function generateResolutionSummary(Scope $scope, MutationSuggestion $suggestion): string
    {
        return GeneralUtility::hmac(
            json_encode([
                $scope,
                $suggestion->identifier,
                $suggestion->collection,
            ]),
            self::class,
        );
    }

    protected function resolveReportUuids(Report ...$reports): array
    {
        return array_map(static fn(Report $report) => $report->uuid, $reports);
    }

    protected function isSystemMaintainer(): bool
    {
        $backendUser = $GLOBALS['BE_USER'] ?? null;
        return $backendUser instanceof BackendUserAuthentication && $backendUser->isSystemMaintainer();
    }
}
