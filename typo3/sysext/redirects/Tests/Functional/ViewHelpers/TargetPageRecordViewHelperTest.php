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

namespace TYPO3\CMS\Redirects\Tests\Functional\ViewHelpers;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class TargetPageRecordViewHelperTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['redirects'];

    private function renderTemplate(string $template): mixed
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getViewHelperResolver()->addNamespace('redirects', 'TYPO3\\CMS\\Redirects\\ViewHelpers');
        $context->getTemplatePaths()->setTemplateSource($template);
        return (new TemplateView($context))->render();
    }

    #[Test]
    public function invalidTargetReturnsEmptyArray(): void
    {
        self::assertSame([], $this->renderTemplate('<redirects:targetPageRecord target="nope" />'));
    }

    #[Test]
    public function emptyTargetReturnsEmptyArray(): void
    {
        self::assertSame([], $this->renderTemplate('<redirects:targetPageRecord target="" />'));
    }

    #[Test]
    public function notExistingVariableAsTargetReturnsEmptyArray(): void
    {
        self::assertSame([], $this->renderTemplate('<redirects:targetPageRecord target="{undefinedVar}" />'));
    }

    #[Test]
    public function notExistingPageRecordReturnsEmptyArray(): void
    {
        self::assertSame([], $this->renderTemplate('<redirects:targetPageRecord target="t3://page?uid=9876" />'));
    }

    #[Test]
    public function visibleStandardPageRecordIsReturned(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/targetPageIdViewHelper_pages.csv');
        $result = $this->renderTemplate('<redirects:targetPageRecord target="t3://page?uid=2" />');
        self::assertNotSame([], $result);
        self::assertArrayHasKey('uid', $result);
        self::assertArrayHasKey('doktype', $result);
        self::assertArrayHasKey('hidden', $result);
        self::assertArrayHasKey('deleted', $result);
        self::assertArrayHasKey('title', $result);
        self::assertSame(2, $result['uid']);
        self::assertSame(1, $result['doktype']);
        self::assertSame(0, $result['hidden']);
        self::assertSame(0, $result['deleted']);
        self::assertSame('standard page - not hidden or soft deleted', $result['title']);
    }

    #[Test]
    public function softDeletedPageReturnsEmptyArray(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/targetPageIdViewHelper_pages.csv');
        self::assertSame([], $this->renderTemplate('<redirects:targetPageRecord target="t3://page?uid=3" />'));
    }

    #[Test]
    public function hiddenPageReturnsPageRecord(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/targetPageIdViewHelper_pages.csv');
        $result = $this->renderTemplate('<redirects:targetPageRecord target="t3://page?uid=4" />');
        self::assertNotSame([], $result);
        self::assertArrayHasKey('uid', $result);
        self::assertArrayHasKey('doktype', $result);
        self::assertArrayHasKey('hidden', $result);
        self::assertArrayHasKey('deleted', $result);
        self::assertArrayHasKey('title', $result);
        self::assertSame(4, $result['uid']);
        self::assertSame(1, $result['doktype']);
        self::assertSame(1, $result['hidden']);
        self::assertSame(0, $result['deleted']);
        self::assertSame('standard page - hidden but not soft deleted', $result['title']);
    }
}
