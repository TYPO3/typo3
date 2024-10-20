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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Tests\Functional\Fixtures\Log\DummyWriter;
use TYPO3\CMS\Core\Tests\Functional\Html\DefaultSanitizerBuilderTest;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Fluid\ViewHelpers\Sanitize\HtmlViewHelper;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class HtmlViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $configurationToUseInTestInstance = [
        'LOG' => [
            'TYPO3' => [
                'HtmlSanitizer' => [
                    'writerConfiguration' => [
                        LogLevel::DEBUG => [
                            DummyWriter::class => [],
                        ],
                    ],
                ],
            ],
        ],
    ];

    protected function tearDown(): void
    {
        parent::tearDown();
        DummyWriter::$logs = [];
    }

    public static function isSanitizedDataProvider(): array
    {
        // @todo splitter for functional tests cannot deal with external classes
        return DefaultSanitizerBuilderTest::isSanitizedDataProvider();
    }

    #[DataProvider('isSanitizedDataProvider')]
    #[Test]
    public function isSanitizedUsingNodeInstruction(string|int $payload, string $expectation): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource(sprintf('<f:sanitize.html>%s</f:sanitize.html>', $payload));
        self::assertSame($expectation, (new TemplateView($context))->render());
    }

    #[DataProvider('isSanitizedDataProvider')]
    #[Test]
    public function isSanitizedUsingInlineInstruction(string|int $payload, string $expectation): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('{payload -> f:sanitize.html()}');
        $view = new TemplateView($context);
        $view->assign('payload', $payload);
        self::assertSame($expectation, $view->render());
    }

    #[Test]
    public function incidentIsLogged(): void
    {
        $templatePath = __DIR__ . '/Fixtures/Template.html';
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplatePathAndFilename($templatePath);
        $view = new TemplateView($context);
        $view->assign('payload', '<script>alert(1)</script>');
        $view->render();

        $logItemDataExpectation = [
            'behavior' => 'default',
            'nodeType' => 1,
            'nodeName' => 'script',
            'initiator' => HtmlViewHelper::class,
        ];
        $logItem = end(DummyWriter::$logs);
        self::assertInstanceOf(LogRecord::class, $logItem);
        self::assertSame($logItemDataExpectation, $logItem->getData());
        self::assertSame('TYPO3.HtmlSanitizer.Visitor.CommonVisitor', $logItem->getComponent());
    }
}
