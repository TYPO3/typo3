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

use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

/**
 * Converter which transforms simple types to \TYPO3\CMS\Extbase\Domain\Model\FileReference.
 *
 * @internal
 */
class FileReferenceConverter extends AbstractFileFolderConverter
{
    /**
     * @var string[]
     * @deprecated will be removed in TYPO3 v13.0, as this is defined in Services.yaml.
     */
    protected $sourceTypes = ['integer'];

    /**
     * @var string
     * @deprecated will be removed in TYPO3 v13.0, as this is defined in Services.yaml.
     */
    protected $targetType = FileReference::class;

    /**
     * @var string
     */
    protected $expectedObjectType = \TYPO3\CMS\Core\Resource\FileReference::class;

    /**
     * @param string|int $source
     * @return \TYPO3\CMS\Core\Resource\FileReference
     */
    protected function getOriginalResource($source): ?ResourceInterface
    {
        return $this->fileFactory->getFileReferenceObject($source);
    }
}
