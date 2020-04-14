<?php

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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Format;

use TYPO3\CMS\Fluid\ViewHelpers\Format\DateViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

/**
 * Test case
 */
class DateViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var DateViewHelper|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $subject;

    /**
     * @var string Backup of current timezone, it is manipulated in tests
     */
    protected $timezone;

    /**
     * @var DateViewHelper
     */
    protected $viewHelper;

    protected $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->timezone = @date_default_timezone_get();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] = 'Y-m-d';
        $this->viewHelper = new DateViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->timezone);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function viewHelperFormatsDateCorrectly()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'date' => new \DateTime('1980-12-13')
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('1980-12-13', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperFormatsDateStringCorrectly()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'date' => '1980-12-13'
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('1980-12-13', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRespectsCustomFormat()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'date' => new \DateTime('1980-02-01'),
                'format' => 'd.m.Y'
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('01.02.1980', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperSupportsDateTimeImmutable()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'date' => new \DateTimeImmutable('1980-02-01'),
                'format' => 'd.m.Y'
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('01.02.1980', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperReturnsEmptyStringIfChildrenIsNULL()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return null;
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperReturnsCurrentDateIfEmptyStringIsGiven()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'date' => ''
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $expectedResult = date('Y-m-d', $GLOBALS['EXEC_TIME']);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperReturnsCurrentDateIfChildrenIsEmptyString()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return '';
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $expectedResult = date('Y-m-d', $GLOBALS['EXEC_TIME']);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperUsesDefaultIfNoSystemFormatIsAvailable()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] = '';
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'date' => '@1391876733'
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('2014-02-08', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperUsesSystemFormat()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] = 'l, j. M y';
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'date' => '@1391876733'
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('Saturday, 8. Feb 14', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperThrowsExceptionWithOriginalMessageIfDateStringCantBeParsed()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1241722579);

        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'date' => 'foo'
            ]
        );
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function viewHelperUsesChildNodesIfDateAttributeIsNotSpecified()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return new \DateTime('1980-12-13');
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('1980-12-13', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperUsesChildNodesWithTimestamp()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return '1359891658' . LF;
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('2013-02-03', $actualResult);
    }

    /**
     * @test
     */
    public function dateArgumentHasPriorityOverChildNodes()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'date' => '1980-12-12'
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('1980-12-12', $actualResult);
    }

    /**
     * @test
     */
    public function relativeDateCalculationWorksWithoutBase()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'date' => 'now',
                'format' => 'Y',
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals(date('Y'), $actualResult);
    }

    /**
     * @test
     */
    public function baseArgumentIsConsideredForRelativeDate()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'date' => '-1 year',
                'format' => 'Y',
                'base' => '2017-01-01'
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('2016', $actualResult);
    }

    /**
     * @test
     */
    public function baseArgumentAsDateTimeIsConsideredForRelativeDate()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'date' => '-1 year',
                'format' => 'Y',
                'base' => new \DateTime('2017-01-01')
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('2016', $actualResult);
    }

    /**
     * @test
     */
    public function baseArgumentDoesNotAffectAbsoluteTime()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'date' => '@1435784732',
                'format' => 'Y',
                'base' => 1485907200
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('2015', $actualResult);
    }

    /**
     * Data provider for viewHelperRespectsDefaultTimezoneForIntegerTimestamp
     *
     * @return array
     */
    public function viewHelperRespectsDefaultTimezoneForIntegerTimestampDataProvider()
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
    public function viewHelperRespectsDefaultTimezoneForIntegerTimestamp($timezone, $expected)
    {
        $date = 1359891658; // 2013-02-03 11:40 UTC
        $format = 'Y-m-d H:i';

        date_default_timezone_set($timezone);
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'date' => $date,
                'format' => $format
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals($expected, $actualResult);
    }

    /**
     * Data provider for viewHelperRespectsDefaultTimezoneForStringTimestamp
     *
     * @return array
     */
    public function viewHelperRespectsDefaultTimezoneForStringTimestampDataProvider()
    {
        return [
            'Europe/Berlin UTC' => [
                'Europe/Berlin',
                '@1359891658',
                '2013-02-03 12:40'
            ],
            'Europe/Berlin Moscow' => [
                'Europe/Berlin',
                '03/Oct/2000:14:55:36 +0400',
                '2000-10-03 12:55'
            ],
            'Asia/Riyadh UTC' => [
                'Asia/Riyadh',
                '@1359891658',
                '2013-02-03 14:40'
            ],
            'Asia/Riyadh Moscow' => [
                'Asia/Riyadh',
                '03/Oct/2000:14:55:36 +0400',
                '2000-10-03 13:55'
            ],
        ];
    }

    /**
     * @dataProvider viewHelperRespectsDefaultTimezoneForStringTimestampDataProvider
     *
     * @test
     */
    public function viewHelperRespectsDefaultTimezoneForStringTimestamp($timeZone, $date, $expected)
    {
        $format = 'Y-m-d H:i';

        date_default_timezone_set($timeZone);
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'date' => $date,
                'format' => $format
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals($expected, $actualResult);
    }

    /**
     * Data provider for dateViewHelperFormatsDateLocalizedDataProvider
     *
     * @return array
     */
    public function dateViewHelperFormatsDateLocalizedDataProvider()
    {
        return [
            'de_DE.UTF-8' => [
                'de_DE.UTF-8',
                '03. Februar 2013'
            ],
            'en_ZW.utf8' => [
                'en_ZW.utf8',
                '03. February 2013'
            ]
        ];
    }

    /**
     * @dataProvider dateViewHelperFormatsDateLocalizedDataProvider
     *
     * @test
     */
    public function dateViewHelperFormatsDateLocalized($locale, $expected)
    {
        $format = '%d. %B %Y';
        // 2013-02-03 11:40 UTC
        $timestamp = '@1359891658';

        try {
            $this->setLocale(LC_COLLATE, $locale);
            $this->setLocale(LC_CTYPE, $locale);
            $this->setLocale(LC_MONETARY, $locale);
            $this->setLocale(LC_TIME, $locale);
        } catch (\PHPUnit\Framework\Exception $e) {
            self::markTestSkipped('Locale ' . $locale . ' is not available.');
        }
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'date' => $timestamp,
                'format' => $format
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals($expected, $actualResult);
    }
}
