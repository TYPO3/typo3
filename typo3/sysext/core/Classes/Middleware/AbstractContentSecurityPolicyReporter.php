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

namespace TYPO3\CMS\Core\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\PolicyProvider;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\Report;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\ReportDetails;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\ReportRepository;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\ReportStatus;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\IpAnonymizationUtility;

/**
 * @internal
 */
abstract class AbstractContentSecurityPolicyReporter implements MiddlewareInterface
{
    protected const URI_KEYS = ['document-uri', 'report-uri', 'blocked-uri', 'referrer'];

    public function __construct(
        protected readonly PolicyProvider $policyProvider,
        protected readonly ReportRepository $reportRepository
    ) {
    }

    protected function persistCspReport(Scope $scope, ServerRequestInterface $request): void
    {
        $payload = (string)$request->getBody();
        if (!$this->isJson($payload)) {
            return;
        }
        $normalizedParams = $request->getAttribute('normalizedParams');
        $meta = [
            'addr' => IpAnonymizationUtility::anonymizeIp($normalizedParams->getRemoteAddress()),
            'agent' => $normalizedParams->getHttpUserAgent(),
        ];
        $requestTime = (int)($request->getQueryParams()['requestTime'] ?? 0);
        $originalDetails = json_decode($payload, true)['csp-report'] ?? [];
        $originalDetails = $this->anonymizeDetails($originalDetails);
        $details = new ReportDetails($originalDetails);
        $summary = $this->generateReportSummary($scope, $details);
        $report = new Report(
            $scope,
            ReportStatus::New,
            $requestTime,
            $meta,
            $details,
            $summary
        );
        $this->reportRepository->add($report);
    }

    protected function generateReportSummary(Scope $scope, ReportDetails $details): string
    {
        return GeneralUtility::hmac(
            json_encode([
                $scope,
                $details['effective-directive'],
                $details['blocked-uri'],
                $details['script-sample'] ?? null,
            ]),
            self::class,
        );
    }

    protected function anonymizeDetails(array $details): array
    {
        foreach (self::URI_KEYS as $uriKey) {
            if (!isset($details[$uriKey])) {
                continue;
            }
            $details[$uriKey] = $this->anonymizeUri($details[$uriKey]);
        }
        return $details;
    }

    protected function anonymizeUri(string $value): string
    {
        $uri = new Uri($value);
        if ($uri->getQuery() === '') {
            return $value;
        }
        parse_str($uri->getQuery(), $query);
        // strip CSRF token (might be a different usage as well)
        unset($query['token']);
        return (string)$uri->withQuery(http_build_query($query, '', '&', PHP_QUERY_RFC3986));
    }

    protected function isCspReport(Scope $scope, ServerRequestInterface $request): bool
    {
        $normalizedParams = $request->getAttribute('normalizedParams');
        $contentTypeHeader = $request->getHeaderLine('content-type');

        // @todo
        // + verify current session
        // + invoke rate limiter
        // + check additional scope (snippet enrichment)

        $reportingUriBase = $this->policyProvider->getDefaultReportingUriBase($scope, $request, false);
        return $request->getMethod() === 'POST'
            && str_starts_with($normalizedParams->getRequestUri(), (string)$reportingUriBase)
            && $contentTypeHeader === 'application/csp-report';
    }

    protected function isJson(string $value): bool
    {
        try {
            json_decode($value, false, 16, JSON_THROW_ON_ERROR);
            return true;
        } catch (\JsonException) {
            return false;
        }
    }
}
