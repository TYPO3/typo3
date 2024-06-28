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

namespace TYPO3Tests\TypeConverterTest\Domain\Model;

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class Animals
{
    /**
     * @var ObjectStorage<\TYPO3Tests\TypeConverterTest\Domain\Model\Animal>
     */
    protected ObjectStorage $collection;

    /**
     * @return ObjectStorage<\TYPO3Tests\TypeConverterTest\Domain\Model\Animal>
     */
    public function getCollection(): ObjectStorage
    {
        return $this->collection;
    }

    /**
     * @param ObjectStorage<\TYPO3Tests\TypeConverterTest\Domain\Model\Animal> $collection
     */
    public function setCollection(ObjectStorage $collection): void
    {
        $this->collection = $collection;
    }
}
