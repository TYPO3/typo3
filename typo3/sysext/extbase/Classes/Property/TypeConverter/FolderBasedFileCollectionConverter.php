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

use TYPO3\CMS\Core\Resource\Collection\AbstractFileCollection;
use TYPO3\CMS\Extbase\Domain\Model\FolderBasedFileCollection;

/**
 * Converter which transforms simple types to \TYPO3\CMS\Extbase\Domain\Model\FileCollection.
 *
 * @internal experimental! This class is experimental and subject to change!
 * @deprecated since TYPO3 10.4, will be removed in version 11.0
 */
class FolderBasedFileCollectionConverter extends AbstractFileCollectionConverter
{
    /**
     * @var string[]
     */
    protected $sourceTypes = ['integer'];

    /**
     * @var string
     */
    protected $targetType = FolderBasedFileCollection::class;

    /**
     * @var string
     */
    protected $expectedObjectType = \TYPO3\CMS\Core\Resource\Collection\FolderBasedFileCollection::class;

    /**
     * @param int $source
     * @return \TYPO3\CMS\Core\Resource\Collection\FolderBasedFileCollection
     */
    protected function getObject($source): AbstractFileCollection
    {
        return $this->fileFactory->getCollectionObject($source);
    }
}
