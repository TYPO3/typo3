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
class ClassWithSetters
{
    /**
     * @var mixed
     */
    public $property1;

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

    public function setProperty3($value)
    {
        $this->property3 = $value;
    }

    protected function setProperty4($value)
    {
        $this->property4 = $value;
    }

    public function getProperty2()
    {
        return $this->property2;
    }
}
