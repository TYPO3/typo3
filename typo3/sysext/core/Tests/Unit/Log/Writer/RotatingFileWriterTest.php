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
        return new RotatingFileWriter(['logFile' => $logFileName]);
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
        $simpleRecord = new LogRecord(StringUtility::getUniqueId('test.core.log.rotatingFileWriter.simpleRecord.'), LogLevel::INFO, 'test record');
        $writer->writeLog($simpleRecord);

        $rotatedFiles = glob($logFileName . '.*');
        self::assertCount(0, $rotatedFiles);
    }

    #[Test]
    public function writingLogWithoutLatestRotationAndNonEmptyLogRotates(): void
    {
        $logFileName = $this->getDefaultFileName();

        file_put_contents($logFileName, 'fooo');

        $writer = new RotatingFileWriter();
        $writer->setLogFile($logFileName);
        $simpleRecord = new LogRecord(StringUtility::getUniqueId('test.core.log.rotatingFileWriter.simpleRecord.'), LogLevel::INFO, 'test record');
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

        $writer = new RotatingFileWriter([
            'interval' => $interval,
            'logFile' => $logFileName,
        ]);
        $simpleRecord = new LogRecord(StringUtility::getUniqueId('test.core.log.rotatingFileWriter.simpleRecord.'), LogLevel::INFO, 'test record');
        $writer->writeLog($simpleRecord);

        $rotatedFiles = glob($logFileName . '.*');
        self::assertCount(1, $rotatedFiles);
    }

    #[DataProvider('intervalDataProvider')]
    #[Test]
    public function writingLogWithExpiredLatestRotationInTimeFrameRotates(Interval $interval): void
    {
        $rotationDateModifierClosure = match ($interval) {
            Interval::MONTHLY, Interval::YEARLY => static function (\DateTimeImmutable $rotationDate) use ($interval): \DateTimeImmutable {
                // This is great – when subtracting 1 month or 1 year, it may happen that the result would be invalid:
                // e.g. 2024-03-30 -1 month -> 2024-02-30, 2024-05-31 -1 month -> 2024-04-31, 2028-02-29 -1 year -> 2027-02-29, etc.
                // PHP thankfully catches this and rolls over to the next(!) valid date:
                // e.g. 2024-03-30 -1 month -> 2024-03-01, 2024-05-31 -1 month -> 2024-05-01, 2028-02-29 -1 year -> 2027-03-01, etc.
                // However, this is not the desired result in this case when SUBTRACTING a month as the previous(!!) valid date is required.
                // For this reason, a custom handling in case of months and years is in place that goes back in time to the first day of
                // the given period, and sets the current day or the last day of the resulting period, whatever fits:
                // e.g. 2024-03-30 -1 month -> 2024-02-29, 2024-05-12 -1 month -> 2024-04-12, 2028-02-29 -1 year -> 2027-02-28, etc.
                if ($interval === Interval::MONTHLY) {
                    $firstDayOfModifier = 'first day of -1 month';
                } else {
                    $firstDayOfModifier = 'first day of -1 year';
                }

                $currentDay = $rotationDate->format('j');
                $rotationDate = $rotationDate->modify($firstDayOfModifier);
                $totalDays = $rotationDate->format('t');
                return $rotationDate->modify('+' . (min($currentDay, $totalDays) - 1) . ' days');
            },
            default => static function (\DateTimeImmutable $rotationDate) use ($interval): \DateTimeImmutable {
                return $rotationDate->sub(new \DateInterval($interval->getDateInterval()));
            },
        };
        $rotationDate = $rotationDateModifierClosure(new \DateTimeImmutable('@' . time()))->format('YmdHis');
        $logFileName = $this->getDefaultFileName();

        file_put_contents($logFileName, 'fooo');
        file_put_contents($logFileName . '.' . $rotationDate, 'fooo');

        $writer = new RotatingFileWriter([
            'interval' => $interval,
            'logFile' => $logFileName,
        ]);
        $simpleRecord = new LogRecord(StringUtility::getUniqueId('test.core.log.rotatingFileWriter.simpleRecord.'), LogLevel::INFO, 'test record');
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

        $writer = new RotatingFileWriter([
            'interval' => Interval::DAILY,
            'logFile' => $logFileName,
            'maxFiles' => 3,
        ]);
        $simpleRecord = new LogRecord(StringUtility::getUniqueId('test.core.log.rotatingFileWriter.simpleRecord.'), LogLevel::INFO, 'test record');
        $writer->writeLog($simpleRecord);

        self::assertFileDoesNotExist($logFileName . '.20230607093215');
    }
}
