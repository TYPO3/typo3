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

namespace TYPO3\CMS\Fluid\Tests\Unit\Core\Component;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Fluid\Core\Component\DeclarativeComponentCollection;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DeclarativeComponentCollectionTest extends UnitTestCase
{
    public static function convertTemplatePatternToRegularExpressionDataProvider(): array
    {
        return [
            'default folder structure' => ['{path}/{name}/{name}', '~^(?<path>(?:.+?/)?)(?<name>[^/]+?)/(?P=name)$~'],
            'simple folder structure' => ['{path}/{name}', '~^(?<path>(?:.+?/)?)(?<name>[^/]+?)$~'],
            'name suffix' => ['{path}/{name}.component', '~^(?<path>(?:.+?/)?)(?<name>[^/]+?)\.component$~'],
            'name prefix' => ['{path}/component.{name}', '~^(?<path>(?:.+?/)?)component\.(?<name>[^/]+?)$~'],
        ];
    }

    #[Test]
    #[DataProvider('convertTemplatePatternToRegularExpressionDataProvider')]
    public function convertTemplatePatternToRegularExpression(string $pattern, string $expectedRegularExpression): void
    {
        $reflectedMethod = new \ReflectionMethod(DeclarativeComponentCollection::class, 'convertTemplatePatternToRegularExpression');
        self::assertSame($expectedRegularExpression, $reflectedMethod->invoke(null, $pattern));
    }
}
