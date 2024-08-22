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

namespace TYPO3\CMS\Fluid\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class ViewhelperLibraryTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    /**
     * This test case covers the usage of standalone PHP libraries that provide
     * Fluid ViewHelper classes. These libraries usually don't use TYPO3's
     * dependency injection container and thus need to be supported separately
     * in TYPO3's ViewHelperResolver implementation.
     */
    #[Test]
    public function viewhelperLibraryCanBeLoadedTest(): void
    {
        $view = new TemplateView();
        $view->getRenderingContext()->getViewHelperResolver()->addNamespace('fl', 'TYPO3Tests\\ViewhelperLibrary\\ViewHelpers');
        $view->getRenderingContext()->getTemplatePaths()->setTemplateSource('<fl:test />');
        self::assertSame('test viewhelper working', $view->render());
    }
}
