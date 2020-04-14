<?php

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

namespace TYPO3\CMS\Extbase\Tests\Fixture;

/**
 * A dummy class with setters for testing data mapping
 */
class ClassWithSettersAndConstructor
{
    /**
     * @var mixed
     */
    protected $property1;

    /**
     * @var mixed
     */
    protected $property2;

    public function __construct($property1)
    {
        $this->property1 = $property1;
    }

    public function getProperty1()
    {
        return $this->property1;
    }

    public function getProperty2()
    {
        return $this->property2;
    }

    public function setProperty2($property2)
    {
        $this->property2 = $property2;
    }
}
