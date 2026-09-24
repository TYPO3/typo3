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
 * Extracts the text nodes of XML files, honoring the encoding of the XML declaration.
 *
 * @internal
 */
#[AsTextExtractor]
final class XmlTextExtractor extends AbstractTextExtractor
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
        return ['text/xml', 'application/xml'];
    }

    /**
     * The charset of the XML declaration, utf-8 if there is none.
     */
    public function detectCharset(string $xml): string
    {
        preg_match('/^[[:space:]]*<\?xml[^>]+encoding[[:space:]]*=[[:space:]]*["\'][[:space:]]*([[:alnum:]_-]+)[[:space:]]*["\']/i', substr($xml, 0, 200), $reg);
        return ($reg[1] ?? '') !== '' ? trim(strtolower($reg[1])) : 'utf-8';
    }

    protected function extractFromLocalFile(SystemResourceInterface $file, string $localFilePath): ExtractedText
    {
        $xml = (string)file_get_contents($localFilePath);
        $text = $this->htmlContentConverter->toUtf8(strip_tags(str_replace('<', ' <', $xml)), $this->detectCharset($xml));
        return new ExtractedText($text, $file->getName());
    }
}
