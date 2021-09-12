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

namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic\Mapper\Fixture;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Fixture
 * @todo Specifying the property types results in test bench errors
 */
class DummyEntity extends AbstractEntity
{
    /**
     * @var string
     */
    public $firstProperty;

    /**
     * @var int
     */
    public $secondProperty;

    /**
     * @var float
     */
    public $thirdProperty;

    /**
     * @var bool
     */
    public $fourthProperty;

    /**
     * no var here :(
     */
    public $unknownType;
}
