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

namespace TYPO3\CMS\Core\Tests\Functional\Cache\Core;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class ClassAliasLoaderTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_aliases_test',
    ];

    #[Test]
    public function aliasMapsFromExtensionsCanBeLoaded(): void
    {
        // @phpstan-ignore-next-line PHPStan does not know about class aliases.
        $viewHelperMock = $this->createMock('\TYPO3\CMS\Fluid\Core\ViewHelper\AliasAbstractViewHelper');
        self::assertInstanceOf(AbstractViewHelper::class, $viewHelperMock);
    }
}
