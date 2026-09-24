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
 * Extracts text of legacy Word documents with "catdoc".
 *
 * @internal
 */
#[AsTextExtractor]
final class CatdocTextExtractor extends AbstractTextExtractor
{
    protected function getMimeTypes(): array
    {
        return ['application/msword'];
    }

    protected function getRequiredTools(): array
    {
        return ['catdoc'];
    }

    protected function extractFromLocalFile(SystemResourceInterface $file, string $localFilePath): ExtractedText
    {
        $command = $this->getToolPath('catdoc') . ' -d utf-8 ' . CommandUtility::escapeShellArgument($localFilePath);
        return new ExtractedText(self::removeEndJunk($this->joinOutput($this->commandExecutor->execute($command))));
    }
}
