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

namespace TYPO3\CMS\Backend\Tests\Functional\ViewHelpers\Link;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class DocumentationViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected ServerRequest $request;

    public function setUp(): void
    {
        parent::setUp();
        $this->request = (new ServerRequest('https://www.example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', new NormalizedParams([], [], '', ''));
    }

    #[Test]
    public function renderReturnsValidLink(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create([], $this->request);
        $context->getViewHelperResolver()->addNamespace('be', 'TYPO3\\CMS\\Backend\\ViewHelpers');
        $context->getTemplatePaths()->setTemplateSource('<be:link.documentation identifier="foobar" class="baz" target="_self" rel="nofollow">see documentation</be:link.documentation>');
        $result = urldecode((new TemplateView($context))->render());

        $typo3Version = (new Typo3Version())->getBranch();
        self::assertSame('<a class="baz" target="_blank" rel="noreferrer" href="https://docs.typo3.org/permalink/foobar@' . $typo3Version . '">see documentation</a>', $result);
    }

    #[Test]
    public function renderThrowsExceptionForInvalidIdentifier(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1728643940);

        $context = $this->get(RenderingContextFactory::class)->create([], $this->request);
        $context->getViewHelperResolver()->addNamespace('be', 'TYPO3\\CMS\\Backend\\ViewHelpers');
        $context->getTemplatePaths()->setTemplateSource('<be:link.documentation identifier="foo@bar" class="baz">see documentation</be:link.documentation>');
        (new TemplateView($context))->render();
    }
}
