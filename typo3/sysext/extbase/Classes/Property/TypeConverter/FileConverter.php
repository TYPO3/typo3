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

namespace TYPO3\CMS\Extbase\Property\TypeConverter;

use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceInterface;

/**
 * Converter which transforms simple types to \TYPO3\CMS\Extbase\Domain\Model\File.
 *
 * @internal
 */
class FileConverter extends AbstractFileFolderConverter
{
    protected string $expectedObjectType = File::class;

    /**
     * @param string|int $source
     * @throws ResourceDoesNotExistException
     */
    protected function getOriginalResource($source): ?ResourceInterface
    {
        return $this->fileFactory->retrieveFileOrFolderObject($source);
    }
}
