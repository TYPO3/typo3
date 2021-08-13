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

use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class NewRecordViewHelperTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected $initializeDatabase = false;

    public function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', new NormalizedParams([], [], '', ''));
    }

    /**
     * @test
     */
    public function renderReturnsValidLinkInExplicitFormat(): void
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename('EXT:backend/Tests/Functional/ViewHelpers/Fixtures/Link/NewRecordViewHelper/WithPidAndTable.html');
        $result = urldecode($view->render());

        self::assertStringContainsString('/typo3/record/edit', $result);
        self::assertStringContainsString('edit[a_table][17]=new', $result);
    }

    /**
     * @test
     */
    public function renderReturnsValidLinkForRoot(): void
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename('EXT:backend/Tests/Functional/ViewHelpers/Fixtures/Link/NewRecordViewHelper/WithTable.html');
        $result = urldecode($view->render());

        self::assertStringContainsString('/typo3/record/edit', $result);
        self::assertStringContainsString('edit[a_table][0]=new', $result);
    }

    /**
     * @test
     */
    public function renderReturnsValidLinkInInlineFormat(): void
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename('EXT:backend/Tests/Functional/ViewHelpers/Fixtures/Link/NewRecordViewHelper/InlineWithPidAndTable.html');
        $result = urldecode($view->render());

        self::assertStringContainsString('/typo3/record/edit', $result);
        self::assertStringContainsString('edit[b_table][17]=new', $result);
    }

    /**
     * @test
     */
    public function renderReturnsValidLinkWithReturnUrl(): void
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename('EXT:backend/Tests/Functional/ViewHelpers/Fixtures/Link/NewRecordViewHelper/WithPidTableAndReturnUrl.html');
        $result = urldecode($view->render());

        self::assertStringContainsString('/typo3/record/edit', $result);
        self::assertStringContainsString('edit[c_table][17]=new', $result);
        self::assertStringContainsString('returnUrl=foo/bar', $result);
    }

    /**
     * @test
     */
    public function renderReturnsValidLinkWithPosition(): void
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename('EXT:backend/Tests/Functional/ViewHelpers/Fixtures/Link/NewRecordViewHelper/WithNegativeUid.html');
        $result = urldecode($view->render());

        self::assertStringContainsString('/typo3/record/edit', $result);
        self::assertStringContainsString('edit[c_table][-11]=new', $result);
    }

    /**
     * @test
     */
    public function renderReturnsValidLinkWithDefaultValue(): void
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename('EXT:backend/Tests/Functional/ViewHelpers/Fixtures/Link/NewRecordViewHelper/WithPidTableAndDefaultValue.html');
        $result = urldecode($view->render());

        self::assertStringContainsString('/typo3/record/edit', $result);
        self::assertStringContainsString('edit[c_table][17]=new', $result);
        self::assertStringContainsString('defVals[c_table][c_field]=c_value', $result);
    }

    /**
     * @test
     */
    public function renderReturnsValidLinkWithDefaultValues(): void
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename('EXT:backend/Tests/Functional/ViewHelpers/Fixtures/Link/NewRecordViewHelper/WithPidTableAndDefaultValues.html');
        $result = urldecode($view->render());

        self::assertStringContainsString('/typo3/record/edit', $result);
        self::assertStringContainsString('edit[c_table][17]=new', $result);
        self::assertStringContainsString('defVals[c_table][c_field]=c_value&amp;defVals[c_table][c_field2]=c_value2', $result);
    }

    /**
     * @test
     */
    public function renderThrowsExceptionForInvalidUidArgument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1526134901);

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename('EXT:backend/Tests/Functional/ViewHelpers/Fixtures/Link/NewRecordViewHelper/WithPositiveUid.html');
        $view->render();
    }
}
