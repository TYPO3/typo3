<?php
namespace TYPO3\CMS\Extbase\Property\TypeConverter;

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

/**
 * Converter which transforms simple types to \TYPO3\CMS\Extbase\Domain\Model\FileReference.
 *
 * @internal experimental! This class is experimental and subject to change!
 */
class FileReferenceConverter extends \TYPO3\CMS\Extbase\Property\TypeConverter\AbstractFileFolderConverter
{
    /**
     * @var array<string>
     */
    protected $sourceTypes = ['integer'];

    /**
     * @var string
     */
    protected $targetType = \TYPO3\CMS\Extbase\Domain\Model\FileReference::class;

    /**
     * @var string
     */
    protected $expectedObjectType = \TYPO3\CMS\Core\Resource\FileReference::class;

    /**
     * @param int $source
     * @return \TYPO3\CMS\Core\Resource\FileReference
     */
    protected function getOriginalResource($source)
    {
        return $this->fileFactory->getFileReferenceObject($source);
    }
}
