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

namespace TYPO3\CMS\Backend\Tests\Functional\Preview;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Preview\FluidBasedRecordPreviewRenderer;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\Event\PageContentPreviewRenderingEvent;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FluidBasedRecordPreviewRendererTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3/sysext/backend/Tests/Functional/Fixtures/Extensions/test_fluid_based_record_preview'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages_content_preview.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tt_content_preview.csv');
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $GLOBALS['BE_USER'] = $this->setUpBackendUser(1);

        $schemaFactory = $this->getContainer()->get(TcaSchemaFactory::class);
        $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'] = [
            [
                'label' => 'Blog',
                'value' => 'blog_pi1',
            ],
            [
                'label' => 'Blog',
                'value' => 'blog_pi2',
            ],
        ];
        $schemaFactory->rebuild($GLOBALS['TCA']);
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    public static function pageContentPreviewIsRendererDataProvider(): iterable
    {
        yield 'CType "header" with template' => [
            1,
            'header',
            '1<h1>Header</h1>',
        ];
        yield 'CType "text" without template or paths' => [
            2,
            'text',
            '',
        ];
        yield 'CType "uploads" with paths' => [
            3,
            'uploads',
            '<h1>Layout</h1>3<h2>Uploads</h2>',
        ];
        yield 'list_type "blog_pi1" with template' => [
            4,
            'list',
            '4<h1>BlogPi1</h1>',
        ];
        yield 'list_type "blog_pi2" with paths' => [
            5,
            'list',
            '<h1>Layout</h1>5<h2>BlogPi2</h2>',
        ];
    }

    #[DataProvider('pageContentPreviewIsRendererDataProvider')]
    #[Test]
    public function pageContentPreviewIsRenderer(int $uid, string $contentType, string $expected): void
    {
        $row = BackendUtility::getRecord('tt_content', $uid);
        $event = new PageContentPreviewRenderingEvent(
            'tt_content',
            $contentType,
            $row,
            $this->createMock(PageLayoutContext::class)
        );

        $subject = $this->getContainer()->get(FluidBasedRecordPreviewRenderer::class);
        $subject->renderPageContentPreview($event);

        self::assertSame($expected, preg_replace('/\s+/', '', $event->getPreviewContent() ?? ''));
    }
}
