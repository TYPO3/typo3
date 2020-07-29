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

namespace TYPO3\CMS\Core\Resource\Exception;

use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\ProcessedFile;

/**
 * Exception indicating that a file is already processed
 *
 * @internal
 */
class FileAlreadyProcessedException extends Exception
{
    /**
     * @var ProcessedFile
     */
    private $processedFile;

    public function __construct(ProcessedFile $processedFile, int $code = 0)
    {
        $this->processedFile = $processedFile;
        parent::__construct(sprintf('File "%s" has already been processed', $processedFile->getIdentifier()), $code);
    }

    /**
     * @return ProcessedFile
     */
    public function getProcessedFile(): ProcessedFile
    {
        return $this->processedFile;
    }
}
