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

/**
 * Extracts text of legacy PowerPoint presentations with "ppthtml".
 *
 * @internal
 */
#[AsTextExtractor]
final class LegacyPowerPointTextExtractor extends AbstractTextExtractor
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
        return ['application/vnd.ms-powerpoint'];
    }

    protected function getRequiredTools(): array
    {
        return ['ppthtml'];
    }

    protected function extractFromLocalFile(SystemResourceInterface $file, string $localFilePath): ExtractedText
    {
        $command = $this->getToolPath('ppthtml') . ' ' . CommandUtility::escapeShellArgument($localFilePath);
        $html = $this->htmlContentConverter->toUtf8($this->joinOutput($this->commandExecutor->execute($command)));
        $content = $this->htmlContentConverter->split(self::removeEndJunk($html));
        return new ExtractedText($content->text, $file->getName(), $content->description, $content->keywords);
    }
}
