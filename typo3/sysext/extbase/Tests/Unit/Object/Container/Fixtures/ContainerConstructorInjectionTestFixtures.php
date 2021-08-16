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

namespace TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures;

class SimpleTypeConstructorArgument
{
    public bool $foo;

    /**
     * @param bool $foo
     */
    public function __construct(bool $foo = false)
    {
        $this->foo = $foo;
    }
}

class ArgumentTestClass
{
}

class MandatoryConstructorArgument
{
    public ArgumentTestClass $argumentTestClass;
    public array $allArguments;

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
    public ?ArgumentTestClass $argumentTestClass;

    /**
     * @param ArgumentTestClass|null $argumentTestClass
     */
    public function __construct(ArgumentTestClass $argumentTestClass = null)
    {
        $this->argumentTestClass = $argumentTestClass;
    }
}

class MandatoryConstructorArgumentTwo
{
    public ArgumentTestClass $argumentTestClass;
    public ArgumentTestClass $argumentTestClassTwo;

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
    public ArgumentTestClass $argumentTestClass;
    public ?ArgumentTestClass $argumentTestClassTwo;

    /**
     * @param ArgumentTestClass $argumentTestClass
     * @param ArgumentTestClass|null $argumentTestClassTwo
     */
    public function __construct(ArgumentTestClass $argumentTestClass, ArgumentTestClass $argumentTestClassTwo = null)
    {
        $this->argumentTestClass = $argumentTestClass;
        $this->argumentTestClassTwo = $argumentTestClassTwo;
    }
}

class TwoConstructorArgumentsFirstOptional
{
    public ?ArgumentTestClass $argumentTestClass;
    public ArgumentTestClass $argumentTestClassTwo;

    /**
     * The extbase container code uses PHP parameter reflection isOptional() to determine
     * injection. PHP behaves differently in current supported core versions, in effect
     * constructor injection of the first argument can not be relied on.
     *
     * The according unit tests currently do not check the value of first argument.
     *
     * @see https://bugs.php.net/bug.php?id=62715
     *
     * @param ArgumentTestClass|null $argumentTestClass
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
    public ?ArgumentTestClass $argumentTestClass;
    public ?ArgumentTestClass $argumentTestClassTwo;

    /**
     * @param ArgumentTestClass|null $argumentTestClass
     * @param ArgumentTestClass|null $argumentTestClassTwo
     */
    public function __construct(ArgumentTestClass $argumentTestClass = null, ArgumentTestClass $argumentTestClassTwo = null)
    {
        $this->argumentTestClass = $argumentTestClass;
        $this->argumentTestClassTwo = $argumentTestClassTwo;
    }
}
