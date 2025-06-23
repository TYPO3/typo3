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

namespace TYPO3\CMS\Fluid\Tests\Functional\Core\ViewHelper;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Fluid\View\FluidViewAdapter;
use TYPO3\CMS\Fluid\View\FluidViewFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ViewHelperResolverTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/fluid/Tests/Functional/Fixtures/Extensions/resolverdelegate_test',
    ];

    #[Test]
    public function resolverDelegateCanInjectService(): void
    {
        /** @var FluidViewAdapter */
        $view = $this->get(FluidViewFactory::class)->create(new ViewFactoryData());
        $view->getRenderingContext()->getTemplatePaths()->setTemplateSource(
            '{namespace test=TYPO3Tests\ResolverdelegateTest\Fluid\TestViewHelperResolverDelegate}<test:foo />|<test:bar />'
        );
        self::assertSame('catchall|catchall', $view->render());
    }
}
