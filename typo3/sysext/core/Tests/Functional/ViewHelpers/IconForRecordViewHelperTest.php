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

namespace TYPO3\CMS\Core\Tests\Functional\ViewHelpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\Core\ViewHelper\InvalidArgumentValueException;
use TYPO3Fluid\Fluid\View\TemplateView;

final class IconForRecordViewHelperTest extends FunctionalTestCase
{
    #[Test]
    public function renderRendersIconCallingIconFactoryAccordingToGivenArguments(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource(
            '<core:iconForRecord table="tt_content" row="{uid: 123}" size="large" alternativeMarkupIdentifier="inline" />'
        );
        self::assertStringContainsString('<span class="t3js-icon icon icon-size-large icon-state-default icon-mimetypes-x-content-text" data-identifier="mimetypes-x-content-text" aria-hidden="true">', (new TemplateView($context))->render());
    }

    public static function recordObjectTemplateSourceDataProvider(): iterable
    {
        yield 'record argument' => ['<core:iconForRecord record="{record}" size="large" />'];
        yield 'row argument' => ['<core:iconForRecord row="{record}" size="large" />'];
        yield 'record argument with table' => ['<core:iconForRecord table="tt_content" record="{record}" size="large" />'];
    }

    #[DataProvider('recordObjectTemplateSourceDataProvider')]
    #[Test]
    public function renderRendersIconForRecordObject(string $templateSource): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $record = $this->get(RecordFactory::class)->createFromDatabaseRow(
            'tt_content',
            BackendUtility::getRecord('tt_content', 5)
        );
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($templateSource);
        $view = new TemplateView($context);
        $view->assign('record', $record);

        self::assertStringContainsString('data-identifier="mimetypes-x-content-text-media"', $view->render());
    }

    #[Test]
    public function renderThrowsExceptionIfBothRowAndRecordAreGiven(): void
    {
        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionCode(1788361133);
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource(
            '<core:iconForRecord table="tt_content" row="{uid: 123}" record="{uid: 123}" />'
        );
        (new TemplateView($context))->render();
    }

    #[Test]
    public function renderThrowsExceptionIfNeitherRowNorRecordIsGiven(): void
    {
        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionCode(1788361134);
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<core:iconForRecord table="tt_content" />');
        (new TemplateView($context))->render();
    }

    #[Test]
    public function renderThrowsExceptionIfTableIsMissingForRecordRow(): void
    {
        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionCode(1788361135);
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<core:iconForRecord row="{uid: 123}" />');
        (new TemplateView($context))->render();
    }
}
