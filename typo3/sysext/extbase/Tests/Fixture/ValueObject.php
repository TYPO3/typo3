<?php
namespace TYPO3\CMS\Extbase\Tests\Fixture;

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
 * An entity
 */
class ValueObject extends \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject
{
    /**
     * The value object's name
     *
     * @var string
     */
    protected $name;

    /**
     * Constructs this value object
     *
     * @param string $name Name of this blog
     */
    public function __construct($name)
    {
        $this->setName($name);
    }

    /**
     * Sets this value object's name
     *
     * @param string $name The value object's name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the value object's name
     *
     * @return string The value object's name
     */
    public function getName()
    {
        return $this->name;
    }
}
