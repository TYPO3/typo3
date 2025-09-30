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

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence\Generic\Mapper\Fixtures;

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class ColumnMapFactoryEntityFixture
{
    public ColumnMapFactoryEntityFixture $hasOne;

    public ColumnMapFactoryEntityFixture $hasOneViaIntermediateTable;

    /**
     * @var ObjectStorage<ColumnMapFactoryEntityFixture>
     */
    public ObjectStorage $hasAndBelongsToMany;

    /**
     * @var ObjectStorage<ColumnMapFactoryEntityFixture>
     */
    public ObjectStorage $hasMany;

    /**
     * Note: We do not use int[] here as this would be resolved via "builtInType"
     */
    public array $hasManyStatic;
}
