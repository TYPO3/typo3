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

namespace TYPO3\CMS\IndexedSearch\Tests\Unit\TextExtraction;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class ExternalToolConfigurationTest extends AbstractTextExtractorTestCase
{
    #[Test]
    public function toolPathIsNullWithoutConfiguredDirectory(): void
    {
        $subject = $this->createToolConfiguration(['catdoc' => ''], ['catdoc']);
        self::assertNull($subject->getToolPath('catdoc'));
    }

    #[Test]
    public function toolPathIsNullIfExecutableDoesNotExist(): void
    {
        $subject = $this->createToolConfiguration();
        self::assertNull($subject->getToolPath('catdoc'));
    }

    #[Test]
    public function toolPathIsReturnedIfExecutableExists(): void
    {
        $subject = $this->createToolConfiguration([], ['catdoc']);
        self::assertSame($this->workingDirectory . '/bin/catdoc', $subject->getToolPath('catdoc'));
    }

    #[Test]
    public function trailingSlashOfConfiguredDirectoryIsIgnored(): void
    {
        $subject = $this->createToolConfiguration(['unzip' => $this->workingDirectory . '/bin/'], ['unzip']);
        self::assertSame($this->workingDirectory . '/bin/unzip', $subject->getToolPath('unzip'));
    }

    #[Test]
    public function pdfToolsShareOneConfiguredDirectory(): void
    {
        $subject = $this->createToolConfiguration([], ['pdfinfo', 'pdftotext']);
        self::assertSame($this->workingDirectory . '/bin/pdfinfo', $subject->getToolPath('pdfinfo'));
        self::assertSame($this->workingDirectory . '/bin/pdftotext', $subject->getToolPath('pdftotext'));
    }

    #[Test]
    public function extensionsAreIgnoredCaseInsensitively(): void
    {
        $subject = $this->createToolConfiguration(['ignoreExtensions' => 'PDF, doc ,']);
        self::assertTrue($subject->isIgnored('pdf'));
        self::assertTrue($subject->isIgnored('DOC'));
        self::assertFalse($subject->isIgnored('docx'));
    }

    #[Test]
    public function nothingIsIgnoredByDefault(): void
    {
        self::assertFalse($this->createToolConfiguration()->isIgnored('pdf'));
    }

    public static function pdfModeDataProvider(): array
    {
        return [
            'in range' => [-20, -20],
            'positive' => [5, 5],
            'too high' => [500, 100],
            'too low' => [-500, -100],
        ];
    }

    #[DataProvider('pdfModeDataProvider')]
    #[Test]
    public function pdfModeIsLimited(int $configured, int $expected): void
    {
        self::assertSame($expected, $this->createToolConfiguration(['pdf_mode' => $configured])->getPdfMode());
    }
}
