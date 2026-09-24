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

namespace TYPO3\CMS\IndexedSearch\TextExtraction;

use TYPO3\CMS\Core\Attribute\AsTextExtractor;
use TYPO3\CMS\Core\Resource\TextExtraction\ExtractedText;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Extracts text of PDF files with "pdfinfo" and "pdftotext", optionally
 * divided into page ranges according to the "pdf_mode" configuration.
 *
 * @internal
 */
#[AsTextExtractor]
final class PdfTextExtractor extends AbstractTextExtractor implements PartitionedTextExtractorInterface
{
    protected function getMimeTypes(): array
    {
        return ['application/pdf'];
    }

    protected function getRequiredTools(): array
    {
        return ['pdfinfo', 'pdftotext'];
    }

    protected function extractFromLocalFile(SystemResourceInterface $file, string $localFilePath): ExtractedText
    {
        return $this->extractPartFromLocalFile($file, $localFilePath, $this->readPdfInfo($localFilePath), '0');
    }

    /**
     * The parts are page ranges such as "1-5", or "0" for the whole document.
     */
    public function withPartitionedDocument(SystemResourceInterface $file, \Closure $callback): mixed
    {
        if (!$this->checkRequiredTools($file)) {
            return $callback(new PartitionedDocument(['0'], fn(): ExtractedText => new ExtractedText('')));
        }
        return $this->withLocalFile($file, function (string $localFilePath) use ($file, $callback): mixed {
            $pdfInfo = $this->readPdfInfo($localFilePath);
            return $callback(new PartitionedDocument(
                self::calculateParts((int)($pdfInfo['pages'] ?? 0), $this->toolConfiguration->getPdfMode()),
                fn(string $part): ExtractedText => $this->extractPartFromLocalFile($file, $localFilePath, $pdfInfo, $part)
            ));
        });
    }

    /**
     * Divides a number of pages into page ranges. A positive PDF mode is the number of
     * pages per range, a negative one the (absolute) number of ranges, zero means one range.
     *
     * @return list<string>
     */
    public static function calculateParts(int $pages, int $pdfMode): array
    {
        if ($pages < 1) {
            return ['0'];
        }
        if ($pdfMode > 0) {
            $iterations = (int)ceil($pages / $pdfMode);
        } else {
            $iterations = MathUtility::forceIntegerInRange(abs($pdfMode), 1, $pages);
        }
        $parts = [];
        for ($a = 0; $a < $iterations; $a++) {
            $low = (int)floor($a * ($pages / $iterations)) + 1;
            $high = (int)floor(($a + 1) * ($pages / $iterations));
            $parts[] = $low . '-' . $high;
        }
        return $parts;
    }

    /**
     * Analysing the output lines of "pdfinfo" into an array of the first occurrence of each key.
     *
     * @param list<string> $pdfInfoArray
     * @return array<string, string>
     */
    public function splitPdfInfo(array $pdfInfoArray): array
    {
        $res = [];
        foreach ($pdfInfoArray as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) > 1 && trim($parts[0])) {
                $key = strtolower(trim($parts[0]));
                if (!isset($res[$key])) {
                    $res[$key] = trim($parts[1]);
                }
            }
        }
        return $res;
    }

    /**
     * @return array<string, string>
     */
    private function readPdfInfo(string $localFilePath): array
    {
        $command = $this->getToolPath('pdfinfo') . ' -enc UTF-8 ' . CommandUtility::escapeShellArgument($localFilePath);
        return $this->splitPdfInfo($this->commandExecutor->execute($command));
    }

    /**
     * @param array<string, string> $pdfInfo
     */
    private function extractPartFromLocalFile(SystemResourceInterface $file, string $localFilePath, array $pdfInfo, string $part): ExtractedText
    {
        $pages = (int)($pdfInfo['pages'] ?? 0);
        $content = '';
        if ($pages > 0) {
            [$low, $high] = array_map(intval(...), explode('-', $part, 2) + [0, 0]);
            if ($low < 1 || $high < 1) {
                [$low, $high] = [1, $pages];
            }
            $tempFileName = GeneralUtility::tempnam('Typo3_indexer');
            if (@is_file($tempFileName)) {
                unlink($tempFileName);
            }
            $this->commandExecutor->execute($this->buildTextCommand($localFilePath, $tempFileName, $low, $high));
            if (@is_file($tempFileName)) {
                $content = self::removeEndJunk((string)file_get_contents($tempFileName));
                unlink($tempFileName);
            } else {
                $this->logger?->warning('Text extraction of PDF file "{file}" failed', ['file' => $file->getResourceIdentifier()]);
            }
        }
        return new ExtractedText($content, $pdfInfo['title'] ?? '');
    }

    private function buildTextCommand(string $localFilePath, string $tempFileName, int $low, int $high): string
    {
        return $this->getToolPath('pdftotext') . ' -f ' . CommandUtility::escapeShellArgument((string)$low) . ' -l ' . CommandUtility::escapeShellArgument((string)$high)
            . ' -enc UTF-8 -q ' . CommandUtility::escapeShellArgument($localFilePath) . ' ' . CommandUtility::escapeShellArgument($tempFileName);
    }
}
