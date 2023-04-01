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

use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Tests\Functional\Fixtures\Log\DummyWriter;
use TYPO3\CMS\Core\Tests\Functional\Html\DefaultSanitizerBuilderTest;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Fluid\ViewHelpers\Sanitize\HtmlViewHelper;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class HtmlViewHelperTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected $initializeDatabase = false;

    protected $configurationToUseInTestInstance = [
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

    /**
     * @param string $payload
     * @param string $expectation
     * @test
     * @dataProvider isSanitizedDataProvider
     */
    public function isSanitizedUsingNodeInstruction(string $payload, string $expectation): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource(sprintf('<f:sanitize.html>%s</f:sanitize.html>', $payload));
        self::assertSame($expectation, $view->render());
    }

    /**
     * @param string $payload
     * @param string $expectation
     * @test
     * @dataProvider isSanitizedDataProvider
     */
    public function isSanitizedUsingInlineInstruction(string $payload, string $expectation): void
    {
        $view = new StandaloneView();
        $view->assign('payload', $payload);
        $view->setTemplateSource('{payload -> f:sanitize.html()}');
        self::assertSame($expectation, $view->render());
    }

    /**
     * @test
     */
    public function incidentIsLogged(): void
    {
        $templatePath = __DIR__ . '/Fixtures/Template.html';
        $view = new StandaloneView();
        $view->setTemplatePathAndFilename($templatePath);
        $view->assign('payload', '<script>alert(1)</script>');
        $view->render();

        $logItemDataExpectation = [
            'behavior' => 'default',
            'nodeName' => 'script',
            'initiator' => HtmlViewHelper::class,
        ];
        $logItem = end(DummyWriter::$logs);
        self::assertInstanceOf(LogRecord::class, $logItem);
        self::assertSame($logItemDataExpectation, $logItem->getData());
        self::assertSame('TYPO3.HtmlSanitizer.Visitor.CommonVisitor', $logItem->getComponent());
    }
}
