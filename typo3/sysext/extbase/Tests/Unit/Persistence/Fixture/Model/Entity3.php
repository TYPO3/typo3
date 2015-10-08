<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Fixture\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A model fixture used for testing the persistence manager
 *
 */
class Entity3 extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * Just a normal string
     *
     * @var string
     */
    public $someString;

    /**
     * @var int
     */
    public $someInteger;
}
