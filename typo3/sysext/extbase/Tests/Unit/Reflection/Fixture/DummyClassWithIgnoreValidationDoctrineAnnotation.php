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

namespace TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture;

use TYPO3\CMS\Extbase\Annotation\IgnoreValidation;

/**
 * Dummy class with @TYPO3\CMS\Extbase\Annotation\IgnoreValidation annotation
 * Note: This class is excluded from phpstan analysing, because of errors which are test-purpose related.
 */
class DummyClassWithIgnoreValidationDoctrineAnnotation
{
    /**
     * @param $foo
     * @param $bar
     * @IgnoreValidation("foo")
     * @IgnoreValidation("bar")
     */
    public function someAction($foo, $bar): void {}
}
