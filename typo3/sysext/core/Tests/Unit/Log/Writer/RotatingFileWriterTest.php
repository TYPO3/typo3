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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     * @dataProvider writingLogWithLatestRotationInTimeFrameDoesNotRotateDataProvider
     */
    public function writingLogWithLatestRotationInTimeFrameDoesNotRotate(Interval $interval, int $rotationTimestamp): void
    {
        $rotationDate = (new \DateTimeImmutable('@' . $rotationTimestamp))->format('YmdHis');
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

    public static function writingLogWithLatestRotationInTimeFrameDoesNotRotateDataProvider(): array
    {
        $secondsOfADay = 86400;
        $tolerance = 100;

        return [
            [Interval::DAILY, time() - $secondsOfADay + $tolerance],
            [Interval::WEEKLY, time() - $secondsOfADay * 7 + $tolerance],
            [Interval::MONTHLY, time() - $secondsOfADay * 30 + $tolerance],
            [Interval::YEARLY, time() - $secondsOfADay * 365 + $tolerance],
        ];
    }

    /**
     * @test
     * @dataProvider writingLogWithExpiredLatestRotationInTimeFrameRotatesDataProvider
     */
    public function writingLogWithExpiredLatestRotationInTimeFrameRotates(Interval $interval, int $rotationTimestamp): void
    {
        $rotationDate = (new \DateTimeImmutable('@' . $rotationTimestamp))->format('YmdHis');
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

    public static function writingLogWithExpiredLatestRotationInTimeFrameRotatesDataProvider(): array
    {
        $secondsOfADay = 86400;
        // Helper variable to ensure the next rotation interval kicks in
        $boost = 100;

        return [
            [Interval::DAILY, time() - $secondsOfADay - $boost],
            [Interval::WEEKLY, time() - $secondsOfADay * 7 - $boost],
            [Interval::MONTHLY, time() - $secondsOfADay * 31 - $boost], // dumb test, always expect months with 31 days
            [Interval::YEARLY, time() - $secondsOfADay * 366 - $boost], // dumb test, always expect years with 366 days
        ];
    }

    /**
     * @test
     */
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
