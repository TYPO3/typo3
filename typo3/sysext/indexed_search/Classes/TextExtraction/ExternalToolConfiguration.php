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

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Reads the extension configuration of the external tools used to extract text
 * from documents, and the file extensions that are excluded from indexing.
 *
 * @internal
 */
final readonly class ExternalToolConfiguration
{
    /**
     * The extension configuration key holding the directory of a tool
     */
    private const TOOL_CONFIGURATION_KEYS = [
        'pdfinfo' => 'pdftools',
        'pdftotext' => 'pdftools',
        'catdoc' => 'catdoc',
        'ppthtml' => 'ppthtml',
        'xlhtml' => 'xlhtml',
        'unzip' => 'unzip',
        'unrtf' => 'unrtf',
    ];

    public function __construct(private ExtensionConfiguration $extensionConfiguration) {}

    /**
     * File extensions which must not be indexed, as configured in the extension configuration
     */
    public function isIgnored(string $extension): bool
    {
        $ignoredExtensions = GeneralUtility::trimExplode(',', strtolower((string)$this->get('ignoreExtensions')), true);
        return in_array(strtolower($extension), $ignoredExtensions, true);
    }

    /**
     * Absolute path of an executable, or null if the directory is not configured
     * or the executable does not exist in that directory.
     *
     * @param 'pdfinfo'|'pdftotext'|'catdoc'|'ppthtml'|'xlhtml'|'unzip'|'unrtf' $tool
     */
    public function getToolPath(string $tool): ?string
    {
        $directory = trim((string)$this->get(self::TOOL_CONFIGURATION_KEYS[$tool] ?? ''));
        if ($directory === '') {
            return null;
        }
        $path = rtrim($directory, '/') . '/' . $tool . (Environment::isWindows() ? '.exe' : '');
        return @is_file($path) ? $path : null;
    }

    /**
     * Zero: whole PDF file is indexed in one. A positive value indicates the number of
     * pages at a time, for example "5" means 1-5,6-10,... A negative value indicates
     * the number of groups (absolute value), for example "3" groups of 10 pages: 1-4,5-8,9-10
     */
    public function getPdfMode(): int
    {
        return MathUtility::forceIntegerInRange((int)$this->get('pdf_mode'), -100, 100);
    }

    private function get(string $key): mixed
    {
        return $this->extensionConfiguration->get('indexed_search')[$key] ?? null;
    }
}
