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
 * Converter which transforms simple types to \TYPO3\CMS\Extbase\Domain\Model\Folder.
 *
 * @api experimental! This class is experimental and subject to change!
 */
class FolderConverter extends \TYPO3\CMS\Extbase\Property\TypeConverter\AbstractFileFolderConverter implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var array<string>
     */
    protected $sourceTypes = ['string'];

    /**
     * @var string
     */
    protected $targetType = \TYPO3\CMS\Extbase\Domain\Model\Folder::class;

    /**
     * @var string
     */
    protected $expectedObjectType = \TYPO3\CMS\Core\Resource\Folder::class;

    /**
     * @param string $source
     * @return \TYPO3\CMS\Core\Resource\Folder
     */
    protected function getOriginalResource($source)
    {
        return $this->fileFactory->getFolderObjectFromCombinedIdentifier($source);
    }
}
