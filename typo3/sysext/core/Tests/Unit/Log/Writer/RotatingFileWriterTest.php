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

namespace TYPO3\CMS\Core\Tests\Unit\Log\Writer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Writer\Enum\Interval;
use TYPO3\CMS\Core\Log\Writer\RotatingFileWriter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RotatingFileWriterTest extends UnitTestCase
{
    protected string $logFileDirectory = 'Log';
    protected string $logFileName = 'test.log';
    protected string $testRoot;
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testRoot = Environment::getVarPath() . '/tests/';
        GeneralUtility::mkdir_deep($this->testRoot . $this->logFileDirectory);
        $this->testFilesToDelete[] = $this->testRoot;
    }

    protected function createWriter(string $prependName = ''): RotatingFileWriter
    {
        $logFileName = $this->getDefaultFileName($prependName);
        if (file_exists($logFileName)) {
            unlink($logFileName);
        }
        return GeneralUtility::makeInstance(RotatingFileWriter::class, [
            'logFile' => $logFileName,
        ]);
    }

    /**
     * @return non-empty-string
     */
    protected function getDefaultFileName(string $prependName = ''): string
    {
        return $this->testRoot . $this->logFileDirectory . '/' . $prependName . $this->logFileName;
    }

    #[Test]
    public function writingLogWithoutLatestRotationAndEmptyLogDoesNotRotate(): void
    {
        $logFileName = $this->getDefaultFileName();

        touch($logFileName);

        $writer = $this->createWriter();
        $simpleRecord = GeneralUtility::makeInstance(LogRecord::class, StringUtility::getUniqueId('test.core.log.rotatingFileWriter.simpleRecord.'), LogLevel::INFO, 'test record');
        $writer->writeLog($simpleRecord);

        $rotatedFiles = glob($logFileName . '.*');
        self::assertCount(0, $rotatedFiles);
    }

    #[Test]
    public function writingLogWithoutLatestRotationAndNonEmptyLogRotates(): void
    {
        $logFileName = $this->getDefaultFileName();

        file_put_contents($logFileName, 'fooo');

        $writer = GeneralUtility::makeInstance(RotatingFileWriter::class);
        $writer->setLogFile($logFileName);
        $simpleRecord = GeneralUtility::makeInstance(LogRecord::class, StringUtility::getUniqueId('test.core.log.rotatingFileWriter.simpleRecord.'), LogLevel::INFO, 'test record');
        $writer->writeLog($simpleRecord);

        $rotatedFiles = glob($logFileName . '.*');
        self::assertCount(1, $rotatedFiles);
    }

    public static function intervalDataProvider(): array
    {
        return [
            [Interval::DAILY],
            [Interval::WEEKLY],
            [Interval::MONTHLY],
            [Interval::YEARLY],
        ];
    }

    #[DataProvider('intervalDataProvider')]
    #[Test]
    public function writingLogWithLatestRotationInTimeFrameDoesNotRotate(Interval $interval): void
    {
        $testingTolerance = 100;
        $rotationDate = (new \DateTime('@' . (time() + $testingTolerance)))
            ->sub(new \DateInterval($interval->getDateInterval()))
            ->format('YmdHis');
        $logFileName = $this->getDefaultFileName();

        file_put_contents($logFileName, 'fooo');
        file_put_contents($logFileName . '.' . $rotationDate, 'fooo');

        $writer = GeneralUtility::makeInstance(RotatingFileWriter::class, [
            'interval' => $interval,
            'logFile' => $logFileName,
        ]);
        $simpleRecord = GeneralUtility::makeInstance(LogRecord::class, StringUtility::getUniqueId('test.core.log.rotatingFileWriter.simpleRecord.'), LogLevel::INFO, 'test record');
        $writer->writeLog($simpleRecord);

        $rotatedFiles = glob($logFileName . '.*');
        self::assertCount(1, $rotatedFiles);
    }

    #[DataProvider('intervalDataProvider')]
    #[Test]
    public function writingLogWithExpiredLatestRotationInTimeFrameRotates(Interval $interval): void
    {
        // Helper variable to ensure the next rotation interval kicks in
        $boost = 100;
        $rotationDate = (new \DateTime('@' . (time() - $boost)))
            ->sub(new \DateInterval($interval->getDateInterval()))
            ->format('YmdHis');
        $logFileName = $this->getDefaultFileName();

        file_put_contents($logFileName, 'fooo');
        file_put_contents($logFileName . '.' . $rotationDate, 'fooo');

        $writer = GeneralUtility::makeInstance(RotatingFileWriter::class, [
            'interval' => $interval,
            'logFile' => $logFileName,
        ]);
        $simpleRecord = GeneralUtility::makeInstance(LogRecord::class, StringUtility::getUniqueId('test.core.log.rotatingFileWriter.simpleRecord.'), LogLevel::INFO, 'test record');
        $writer->writeLog($simpleRecord);

        $rotatedFiles = glob($logFileName . '.*');
        self::assertCount(2, $rotatedFiles);
    }

    #[Test]
    public function rotationRespectsMaxAmountOfFiles(): void
    {
        $logFileName = $this->getDefaultFileName();

        file_put_contents($logFileName, 'fooo');
        file_put_contents($logFileName . '.20230609093215', 'fooo');
        file_put_contents($logFileName . '.20230608093215', 'fooo');
        file_put_contents($logFileName . '.20230607093215', 'fooo');

        $writer = GeneralUtility::makeInstance(RotatingFileWriter::class, [
            'interval' => Interval::DAILY,
            'logFile' => $logFileName,
            'maxFiles' => 3,
        ]);
        $simpleRecord = GeneralUtility::makeInstance(LogRecord::class, StringUtility::getUniqueId('test.core.log.rotatingFileWriter.simpleRecord.'), LogLevel::INFO, 'test record');
        $writer->writeLog($simpleRecord);

        self::assertFileDoesNotExist($logFileName . '.20230607093215');
    }
}
