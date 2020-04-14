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

namespace TYPO3\CMS\Core\Tests\UnitDeprecated\Compatibility\Fixtures;

use TYPO3\CMS\Core\Compatibility\PublicMethodDeprecationTrait;

/**
 * Test class using public method deprecation trait test fixture
 */
class PublicMethodDeprecationTraitTextFixture
{
    use PublicMethodDeprecationTrait;

    protected $deprecatedPublicMethods = [
        'methodMadeProtected' => 'Deprecation text',
        'methodMadeProtectedWithArguments' => 'Deprecation text',
        'methodMadeProtectedWithReturn' => 'Deprecation text',
    ];

    public function standardPublicMethod(): void
    {
        throw new \RuntimeException('test', 1528822131);
    }

    protected function standardProtectedMethod(): void
    {
        // empty
    }

    /**
     * @private
     */
    protected function methodMadeProtected(): void
    {
        throw new \RuntimeException('test', 1528822485);
    }

    /**
     * @private
     */
    protected function methodMadeProtectedWithReturn(): string
    {
        return 'foo';
    }

    /**
     * @private
     * @param string $arg1
     * @param string $arg2
     */
    protected function methodMadeProtectedWithArguments(string $arg1, string $arg2): void
    {
        if ($arg1 === 'foo' && $arg2 === 'bar') {
            throw new \RuntimeException('test', 1528822486);
        }
    }
}
