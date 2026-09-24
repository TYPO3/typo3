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

/**
 * Extracts text, title and meta data of HTML files.
 *
 * @internal
 */
#[AsTextExtractor]
final class HtmlTextExtractor extends AbstractTextExtractor
{
    public function __construct(
        ExternalToolConfiguration $toolConfiguration,
        ExternalCommandExecutor $commandExecutor,
        private readonly HtmlContentConverter $htmlContentConverter,
    ) {
        parent::__construct($toolConfiguration, $commandExecutor);
    }

    protected function getMimeTypes(): array
    {
        return ['text/html'];
    }

    protected function extractFromLocalFile(SystemResourceInterface $file, string $localFilePath): ExtractedText
    {
        $content = $this->htmlContentConverter->toUtf8((string)file_get_contents($localFilePath));
        return $this->htmlContentConverter->split($content);
    }
}
