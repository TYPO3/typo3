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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Format;

use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\View\TemplateView;

class DateViewHelperTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected $initializeDatabase = false;

    /**
     * @var string Backup of current timezone, it is manipulated in tests
     */
    protected $timezone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->timezone = @date_default_timezone_get();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] = 'Y-m-d';
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->timezone);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function viewHelperFormatsDateCorrectly(): void
    {
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.date date="1980-12-13"></f:format.date>');
        self::assertSame('1980-12-13', (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function viewHelperRespectsCustomFormat(): void
    {
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.date format="d.m.Y">1980-02-01</f:format.date>');
        self::assertSame('01.02.1980', (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function viewHelperReturnsEmptyStringIfChildrenIsEmpty(): void
    {
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.date></f:format.date>');
        self::assertSame('', (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function viewHelperReturnsCurrentDateIfEmptyStringIsGiven(): void
    {
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.date date=""></f:format.date>');
        self::assertSame(date('Y-m-d', $GLOBALS['EXEC_TIME']), (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function viewHelperUsesDefaultIfNoSystemFormatIsAvailable(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] = '';
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.date>@1391876733</f:format.date>');
        self::assertSame('2014-02-08', (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function viewHelperUsesSystemFormat(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] = 'l, j. M y';
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.date>@1391876733</f:format.date>');
        self::assertSame('Saturday, 8. Feb 14', (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function viewHelperThrowsExceptionWithOriginalMessageIfDateStringCantBeParsed(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1241722579);
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.date>foo</f:format.date>');
        (new TemplateView($context))->render();
    }

    /**
     * @test
     */
    public function viewHelperUsesChildNodesWithTimestamp(): void
    {
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.date>1359891658</f:format.date>');
        self::assertEquals('2013-02-03', (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function dateArgumentHasPriorityOverChildNodes(): void
    {
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.date date="1980-12-12">1359891658</f:format.date>');
        self::assertEquals('1980-12-12', (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function relativeDateCalculationWorksWithoutBase(): void
    {
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.date date="now" format="Y"/>');
        self::assertEquals(date('Y'), (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function baseArgumentIsConsideredForRelativeDate(): void
    {
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.date date="-1 year" base="2017-01-01" format="Y"/>');
        self::assertEquals('2016', (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function baseArgumentDoesNotAffectAbsoluteTime(): void
    {
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.date date="@1435784732" base="1485907200" format="Y"/>');
        self::assertEquals('2015', (new TemplateView($context))->render());
    }

    public function viewHelperRespectsDefaultTimezoneForIntegerTimestampDataProvider(): array
    {
        return [
            'Europe/Berlin' => [
                'Europe/Berlin',
                '2013-02-03 12:40',
            ],
            'Asia/Riyadh' => [
                'Asia/Riyadh',
                '2013-02-03 14:40',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider viewHelperRespectsDefaultTimezoneForIntegerTimestampDataProvider
     */
    public function viewHelperRespectsDefaultTimezoneForIntegerTimestamp(string $timezone, string $expected): void
    {
        date_default_timezone_set($timezone);
        $date = 1359891658; // 2013-02-03 11:40 UTC
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.date date="' . $date . '" format="Y-m-d H:i"/>');
        self::assertEquals($expected, (new TemplateView($context))->render());
    }

    public function viewHelperRespectsDefaultTimezoneForStringTimestampDataProvider(): array
    {
        return [
            'Europe/Berlin UTC' => [
                'Europe/Berlin',
                '@1359891658',
                '2013-02-03 12:40',
            ],
            'Europe/Berlin Moscow' => [
                'Europe/Berlin',
                '03/Oct/2000:14:55:36 +0400',
                '2000-10-03 12:55',
            ],
            'Asia/Riyadh UTC' => [
                'Asia/Riyadh',
                '@1359891658',
                '2013-02-03 14:40',
            ],
            'Asia/Riyadh Moscow' => [
                'Asia/Riyadh',
                '03/Oct/2000:14:55:36 +0400',
                '2000-10-03 13:55',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider viewHelperRespectsDefaultTimezoneForStringTimestampDataProvider
     */
    public function viewHelperRespectsDefaultTimezoneForStringTimestamp(string $timeZone, string $date, string $expected): void
    {
        date_default_timezone_set($timeZone);
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.date date="' . $date . '" format="Y-m-d H:i"/>');
        self::assertEquals($expected, (new TemplateView($context))->render());
    }
}
