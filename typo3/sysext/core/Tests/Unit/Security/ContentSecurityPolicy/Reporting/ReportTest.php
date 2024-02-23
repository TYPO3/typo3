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

namespace TYPO3\CMS\Core\Tests\Unit\Security\ContentSecurityPolicy\Reporting;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Uid\UuidV4;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\Report;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ReportTest extends UnitTestCase
{
    #[Test]
    public function effectiveDirectiveIsTakenFromViolatedDirective(): void
    {
        $report = Report::fromArray([
            'scope' => 'backend',
            'meta' => json_encode([]),
            'details' => json_encode([
                'document-uri' => 'https://example.org/',
                'violated-directive' => 'script-src',
            ]),
            'uuid' => $this->createUuidString(),
        ]);
        self::assertSame('script-src', $report->details['effective-directive']);
    }

    #[Test]
    public function toArrayUsesNativeDetailKeys(): void
    {
        $details = [
            'document-uri' => 'https://example.org/',
            'effective-directive' => 'script-src',
        ];
        $report = Report::fromArray([
            'scope' => 'backend',
            'meta' => json_encode([]),
            'details' => json_encode($details),
            'uuid' => $this->createUuidString(),
        ]);
        self::assertSame($details, json_decode($report->toArray()['details'], true));
    }

    #[Test]
    public function jsonEncodeUsesCamelCasedDetailKeys(): void
    {
        $details = [
            'document-uri' => 'https://example.org/',
            'effective-directive' => 'script-src',
        ];
        $report = Report::fromArray([
            'scope' => 'backend',
            'meta' => json_encode([]),
            'details' => json_encode($details),
            'uuid' => $this->createUuidString(),
        ]);
        $expectation = [
            'documentUri' => 'https://example.org/',
            'effectiveDirective' => 'script-src',
        ];
        self::assertSame($expectation, json_decode(json_encode($report), true)['details']);
    }

    private function createUuidString(): string
    {
        return (string)(new UuidV4());
    }
}
