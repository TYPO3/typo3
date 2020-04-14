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
 * A dummy class with getters and setters for testing data mapping
 */
class ClassWithGettersAndSetters
{
    /**
     * @var mixed
     */
    protected $property1;

    /**
     * @var mixed
     */
    protected $property2;

    /**
     * @var mixed
     */
    public $property3;

    /**
     * @var mixed
     */
    public $property4;

    /**
     * @param mixed $value
     */
    public function setProperty1($value)
    {
        $this->property1 = $value;
    }

    /**
     * @param mixed $value
     */
    public function setProperty2($value)
    {
        $this->property2 = $value;
    }

    /**
     * @return mixed
     */
    protected function getProperty1()
    {
        return $this->property1;
    }

    /**
     * @return mixed
     */
    public function getProperty2()
    {
        return $this->property2;
    }
}
