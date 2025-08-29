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

namespace TYPO3\CMS\Core\Tests\Unit\Security\ContentSecurityPolicy\Event;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Event\BeforePersistingReportEvent;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\Report;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\ReportDetails;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\ReportStatus;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class BeforePersistingReportEventTest extends UnitTestCase
{
    #[Test]
    public function gettersReturnInitializedObjects(): void
    {
        $report = new Report(
            Scope::backend(),
            ReportStatus::New,
            1234567890,
            [],
            new ReportDetails(['document-uri' => 'https://example.org/', 'effective-directive' => 'script-src']),
        );
        $request = $this->createMock(ServerRequestInterface::class);

        $event = new BeforePersistingReportEvent($report, $request);

        self::assertSame($report, $event->originalReport);
        self::assertSame($request, $event->request);
        self::assertSame($report, $event->report);
    }

    #[Test]
    public function reportCanBeSetToNullToSkipPersistence(): void
    {
        $report = new Report(
            Scope::backend(),
            ReportStatus::New,
            1234567890,
            [],
            new ReportDetails(['document-uri' => 'https://example.org/', 'effective-directive' => 'script-src']),
        );
        $request = $this->createMock(ServerRequestInterface::class);

        $event = new BeforePersistingReportEvent($report, $request);
        $event->report = null;

        self::assertNull($event->report);
        self::assertSame($report, $event->originalReport);
    }

    #[Test]
    public function reportCanBeReplacedWithAlternative(): void
    {
        $originalReport = new Report(
            Scope::backend(),
            ReportStatus::New,
            1234567890,
            [],
            new ReportDetails(['document-uri' => 'https://example.org/', 'effective-directive' => 'script-src']),
        );
        $alternativeReport = new Report(
            Scope::frontend(),
            ReportStatus::New,
            1234567890,
            [],
            new ReportDetails(['document-uri' => 'https://example.com/', 'effective-directive' => 'style-src']),
        );
        $request = $this->createMock(ServerRequestInterface::class);

        $event = new BeforePersistingReportEvent($originalReport, $request);
        $event->report = $alternativeReport;

        self::assertSame($alternativeReport, $event->report);
        self::assertSame($originalReport, $event->originalReport);
    }
}
