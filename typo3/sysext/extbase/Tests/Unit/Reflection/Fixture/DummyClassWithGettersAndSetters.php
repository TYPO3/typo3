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

namespace TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture;

/**
 * Fixture class with getters and setters
 */
class DummyClassWithGettersAndSetters
{
    /**
     * @var mixed
     */
    protected $property;

    /**
     * @var mixed
     */
    protected $anotherProperty;

    /**
     * @var mixed
     */
    protected $property2;

    /**
     * @var bool
     */
    protected $booleanProperty = true;

    /**
     * @var mixed
     */
    protected $protectedProperty;

    /**
     * @var string
     */
    protected $unexposedProperty = 'unexposed';

    /**
     * @var mixed
     */
    public $publicProperty;

    /**
     * @var int
     */
    public $publicProperty2 = 42;

    /**
     * @var bool
     */
    protected $anotherBooleanProperty = true;

    /**
     * @param mixed $property
     */
    public function setProperty($property)
    {
        $this->property = $property;
    }

    /**
     * @return mixed
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * @param mixed $anotherProperty
     */
    public function setAnotherProperty($anotherProperty)
    {
        $this->anotherProperty = $anotherProperty;
    }

    /**
     * @return mixed
     */
    public function getAnotherProperty()
    {
        return $this->anotherProperty;
    }

    /**
     * @return mixed
     */
    public function getProperty2()
    {
        return $this->property2;
    }

    /**
     * @param mixed $property2
     */
    public function setProperty2($property2)
    {
        $this->property2 = $property2;
    }

    /**
     * @return string
     */
    protected function getProtectedProperty()
    {
        return '42';
    }

    /**
     * @param mixed $value
     */
    protected function setProtectedProperty($value)
    {
        $this->protectedProperty = $value;
    }

    /**
     * @return bool
     */
    public function isBooleanProperty()
    {
        return $this->booleanProperty;
    }

    /**
     * @return string
     */
    protected function getPrivateProperty()
    {
        return '21';
    }

    /**
     * @param mixed $value
     */
    public function setWriteOnlyMagicProperty($value)
    {
    }

    /**
     * sets the AnotherBooleanProperty
     *
     * @param bool $anotherBooleanProperty
     */
    public function setAnotherBooleanProperty($anotherBooleanProperty)
    {
        $this->anotherBooleanProperty = $anotherBooleanProperty;
    }

    /**
     * @return bool
     */
    public function hasAnotherBooleanProperty()
    {
        return $this->anotherBooleanProperty;
    }

    /**
     * @param int $value
     *
     * @return bool
     */
    public function hasSomeValue($value = 42)
    {
        return true;
    }
}
