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

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting;

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Demand DTO for querying Report entities from the corresponding repository.
 * @internal
 */
class ReportDemand
{
    public ?ReportStatus $status = ReportStatus::New;
    public ?Scope $scope = null;
    public ?array $summaries = null;
    public ?int $requestTime = null;
    public bool $afterRequestTime = false;
    public ?string $orderFieldName = 'created';
    public ?string $orderDirection = 'desc';

    public static function create(): self
    {
        return GeneralUtility::makeInstance(self::class);
    }

    public static function forSummaries(array $summaries): self
    {
        $target = self::create();
        $target->status = null;
        $target->summaries = $summaries;
        return $target;
    }
}
