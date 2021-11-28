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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Sanitize;

use TYPO3\CMS\Core\Tests\Functional\Html\DefaultSanitizerBuilderTest;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

class HtmlViewHelperTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected bool $initializeDatabase = false;

    public static function isSanitizedDataProvider(): array
    {
        // @todo splitter for functional tests cannot deal with external classes
        return DefaultSanitizerBuilderTest::isSanitizedDataProvider();
    }

    /**
     * @param string $payload
     * @param string $expectation
     * @test
     * @dataProvider isSanitizedDataProvider
     */
    public function isSanitizedUsingNodeInstruction(string $payload, string $expectation): void
    {
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource(sprintf('<f:sanitize.html>%s</f:sanitize.html>', $payload));
        self::assertSame($expectation, (new TemplateView($context))->render());
    }

    /**
     * @param string $payload
     * @param string $expectation
     * @test
     * @dataProvider isSanitizedDataProvider
     */
    public function isSanitizedUsingInlineInstruction(string $payload, string $expectation): void
    {
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('{payload -> f:sanitize.html()}');
        $view = new TemplateView($context);
        $view->assign('payload', $payload);
        self::assertSame($expectation, $view->render());
    }
}
