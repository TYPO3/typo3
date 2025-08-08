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

namespace TYPO3\CMS\Core\Tests\Functional\View;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\TestViewfactoryTarget\Service\TestService;

final class ViewFactoryInjectionTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_viewfactory_target',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_viewfactory_customview',
    ];

    #[Test]
    public function viewFactoryCanBeReplacedInService(): void
    {
        $subject = $this->get(TestService::class);
        $expectedResult = [
            'template' => 'myTemplate',
            'variables' => ['foo' => 'bar'],
        ];
        self::assertSame($subject->renderSomething('myTemplate', ['foo' => 'bar']), json_encode($expectedResult));
    }
}
