<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture;

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

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Fixture class with @validate annotations
 */
class DummyClassWithValidateAnnotation extends AbstractEntity
{
    protected $propertyWithoutValidateAnnotations;

    /**
     * @validate NotEmpty
     * @validate Empty (Foo=Bar)
     */
    protected $propertyWithValidateAnnotations;

    public function methodWithoutValidateAnnotations()
    {
    }

    /**
     * @param $fooParam
     * @validate $fooParam FooValidator (FooValidatorOptionKey=FooValidatorOptionValue)
     * @validate $fooParam BarValidator
     */
    public function methodWithValidateAnnotations($fooParam)
    {
    }
}
