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

namespace TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures;

class SimpleTypeConstructorArgument
{
    /**
     * @var bool
     */
    public $foo;

    /**
     * @param bool $foo
     */
    public function __construct($foo = false)
    {
        $this->foo = $foo;
    }
}

class ArgumentTestClass
{
}

class MandatoryConstructorArgument
{
    /**
     * @var ArgumentTestClass
     */
    public $argumentTestClass;

    /**
     * @var array
     */
    public $allArguments;

    /**
     * @param ArgumentTestClass $argumentTestClass
     */
    public function __construct(ArgumentTestClass $argumentTestClass)
    {
        $this->argumentTestClass = $argumentTestClass;
        $this->allArguments = func_get_args();
    }
}

class OptionalConstructorArgument
{
    /**
     * @var ArgumentTestClass
     */
    public $argumentTestClass;

    /**
     * @param ArgumentTestClass $argumentTestClass
     */
    public function __construct(ArgumentTestClass $argumentTestClass = null)
    {
        $this->argumentTestClass = $argumentTestClass;
    }
}

class MandatoryConstructorArgumentTwo
{
    /**
     * @var ArgumentTestClass
     */
    public $argumentTestClass;

    /**
     * @var ArgumentTestClass
     */
    public $argumentTestClassTwo;

    /**
     * @param ArgumentTestClass $argumentTestClass
     * @param ArgumentTestClass $argumentTestClassTwo
     */
    public function __construct(ArgumentTestClass $argumentTestClass, ArgumentTestClass $argumentTestClassTwo)
    {
        $this->argumentTestClass = $argumentTestClass;
        $this->argumentTestClassTwo = $argumentTestClassTwo;
    }
}

class TwoConstructorArgumentsSecondOptional
{
    /**
     * @var ArgumentTestClass
     */
    public $argumentTestClass;

    /**
     * @var ArgumentTestClass
     */
    public $argumentTestClassTwo;

    /**
     * @param ArgumentTestClass $argumentTestClass
     * @param ArgumentTestClass $argumentTestClassTwo
     */
    public function __construct(ArgumentTestClass $argumentTestClass, ArgumentTestClass $argumentTestClassTwo = null)
    {
        $this->argumentTestClass = $argumentTestClass;
        $this->argumentTestClassTwo = $argumentTestClassTwo;
    }
}

class TwoConstructorArgumentsFirstOptional
{
    /**
     * @var ArgumentTestClass
     */
    public $argumentTestClass;

    /**
     * @var ArgumentTestClass
     */
    public $argumentTestClassTwo;

    /**
     * The extbase container code uses PHP parameter reflection isOptional() to determine
     * injection. PHP behaves differently in current supported core versions, in effect
     * constructor injection of the first argument can not be relied on.
     *
     * The according unit tests currently do not check the value of first argument.
     *
     * @see https://bugs.php.net/bug.php?id=62715
     *
     * @param ArgumentTestClass $argumentTestClass
     * @param ArgumentTestClass $argumentTestClassTwo
     */
    public function __construct(ArgumentTestClass $argumentTestClass = null, ArgumentTestClass $argumentTestClassTwo)
    {
        $this->argumentTestClass = $argumentTestClass;
        $this->argumentTestClassTwo = $argumentTestClassTwo;
    }
}

class TwoConstructorArgumentsBothOptional
{
    /**
     * @var ArgumentTestClass
     */
    public $argumentTestClass;

    /**
     * @var ArgumentTestClass
     */
    public $argumentTestClassTwo;

    /**
     * @param ArgumentTestClass $argumentTestClass
     * @param ArgumentTestClass $argumentTestClassTwo
     */
    public function __construct(ArgumentTestClass $argumentTestClass = null, ArgumentTestClass $argumentTestClassTwo = null)
    {
        $this->argumentTestClass = $argumentTestClass;
        $this->argumentTestClassTwo = $argumentTestClassTwo;
    }
}
