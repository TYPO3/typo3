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

/**
 * Fixture class with getters and setters
 */
class DummyClassWithAllTypesOfMethods
{
    public function publicMethod(): void
    {
    }

    protected function protectedMethod(): void
    {
    }

    private function privateMethod(): void
    {
    }

    /**
     * @license
     * @copyright
     * @author
     * @version
     */
    public function methodWithIgnoredTags(): void
    {
    }

    public function injectSettings(): void
    {
        // Will fail, as injectSettings is blacklisted
    }

    public function injectMethodWithoutParam(): void
    {
        // Will fail, as there is no param
    }

    protected function injectMethodThatIsProtected(): void
    {
        // Will fail, as method is protected
    }

    public function injectFoo(DummyClassWithAllTypesOfMethods $foo): void
    {
        // Will succeed
    }

    public static function staticMethod(): void
    {
    }

    public static function methodWithMandatoryParam($param): void
    {
    }

    public static function methodWithNullableParam($param = null): void
    {
    }

    public static function methodWithDefaultValueParam($param = 'foo'): void
    {
    }

    public static function methodWithTypeHintedParam(string $param): void
    {
    }

    /**
     * @param \TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithAllTypesOfMethods $param
     */
    public function methodWithDocBlockTypeHintOnly($param): void
    {
    }
}
