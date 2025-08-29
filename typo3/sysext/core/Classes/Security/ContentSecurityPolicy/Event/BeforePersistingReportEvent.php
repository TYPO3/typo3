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

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy\Event;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\Report;

/**
 * Event that is dispatched before persisting a new
 * `\TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\Report`.
 */
final class BeforePersistingReportEvent
{
    /**
     * @var Report|null Alternative report, or `null` to skip persistence
     */
    public ?Report $report;

    /**
     * @param Report $originalReport The original report created by for the CSP violation
     * @param ServerRequestInterface $request The HTTP POST request submitting the CSP violation
     */
    public function __construct(
        public readonly Report $originalReport,
        public readonly ServerRequestInterface $request,
    ) {
        $this->report = $originalReport;
    }
}
