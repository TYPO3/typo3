<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Format;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\ViewHelpers\Format\DateViewHelper;

/**
 * Test case
 */
class DateViewHelperTest extends UnitTestCase
{
    /**
     * @var array Backup of current locale, it is manipulated in tests
     */
    protected $backupLocales = [];

    /**
     * @var DateViewHelper|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $subject;

    /**
     * @var string Backup of current timezone, it is manipulated in tests
     */
    protected $timezone;

    protected function setUp()
    {
        parent::setUp();
        // Store all locale categories manipulated in tests for reconstruction in tearDown
        $this->backupLocales = [
            'LC_COLLATE' => setlocale(LC_COLLATE, 0),
            'LC_CTYPE' => setlocale(LC_CTYPE, 0),
            'LC_MONETARY' => setlocale(LC_MONETARY, 0),
            'LC_TIME' => setlocale(LC_TIME, 0),
        ];
        $this->timezone = @date_default_timezone_get();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] = 'Y-m-d';
        $this->subject = $this->getAccessibleMock(DateViewHelper::class, ['renderChildren']);
        /** @var RenderingContext $renderingContext */
        $renderingContext = $this->getMock(RenderingContext::class);
        $this->subject->_set('renderingContext', $renderingContext);
    }

    protected function tearDown()
    {
        foreach ($this->backupLocales as $category => $locale) {
            setlocale(constant($category), $locale);
        }
        date_default_timezone_set($this->timezone);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function viewHelperFormatsDateCorrectly()
    {
        $actualResult = $this->subject->render(new \DateTime('1980-12-13'));
        $this->assertEquals('1980-12-13', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperFormatsDateStringCorrectly()
    {
        $actualResult = $this->subject->render('1980-12-13');
        $this->assertEquals('1980-12-13', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRespectsCustomFormat()
    {
        $actualResult = $this->subject->render(new \DateTime('1980-02-01'), 'd.m.Y');
        $this->assertEquals('01.02.1980', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperSupportsDateTimeImmutable()
    {
        $actualResult = $this->subject->render(new \DateTimeImmutable('1980-02-01'), 'd.m.Y');
        $this->assertEquals('01.02.1980', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperReturnsEmptyStringIfChildrenIsNULL()
    {
        $this->subject->expects($this->once())->method('renderChildren')->will($this->returnValue(null));
        $actualResult = $this->subject->render();
        $this->assertEquals('', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperReturnsCurrentDateIfEmptyStringIsGiven()
    {
        $actualResult = $this->subject->render('');
        $expectedResult = (new \DateTime())->format('Y-m-d');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperReturnsCurrentDateIfChildrenIsEmptyString()
    {
        $this->subject->expects($this->once())->method('renderChildren')->will($this->returnValue(''));
        $actualResult = $this->subject->render();
        $expectedResult = (new \DateTime())->format('Y-m-d');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperUsesDefaultIfNoSystemFormatIsAvailable()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] = '';
        $actualResult = $this->subject->render('@1391876733');
        $this->assertEquals('2014-02-08', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperUsesSystemFormat()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] = 'l, j. M y';
        $actualResult = $this->subject->render('@1391876733');
        $this->assertEquals('Saturday, 8. Feb 14', $actualResult);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
     * @expectedExceptionMessageRegExp /"foo" could not be parsed by \\DateTime constructor: .* Unexpected character$/
     */
    public function viewHelperThrowsExceptionWithOriginalMessageIfDateStringCantBeParsed()
    {
        $this->subject->render('foo');
    }

    /**
     * @test
     */
    public function viewHelperUsesChildNodesIfDateAttributeIsNotSpecified()
    {
        $this->subject->expects($this->once())->method('renderChildren')->will($this->returnValue(new \DateTime('1980-12-13')));
        $actualResult = $this->subject->render();
        $this->assertEquals('1980-12-13', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperUsesChildNodesWithTimestamp()
    {
        $this->subject->expects($this->once())->method('renderChildren')->will($this->returnValue('1359891658' . LF));
        $actualResult = $this->subject->render();
        $this->assertEquals('2013-02-03', $actualResult);
    }

    /**
     * @test
     */
    public function dateArgumentHasPriorityOverChildNodes()
    {
        $this->subject->expects($this->never())->method('renderChildren');
        $actualResult = $this->subject->render('1980-12-12');
        $this->assertEquals('1980-12-12', $actualResult);
    }

    /**
     * @test
     */
    public function relativeDateCalculationWorksWithoutBase()
    {
        $this->subject->expects($this->never())->method('renderChildren');
        $actualResult = $this->subject->render('now', 'Y');
        $this->assertEquals(date('Y'), $actualResult);
    }

    /**
     * @test
     */
    public function baseArgumentIsConsideredForRelativeDate()
    {
        $this->subject->expects($this->never())->method('renderChildren');
        $actualResult = $this->subject->render('-1 year', 'Y', '2017-01-01');
        $this->assertEquals('2016', $actualResult);
    }

    /**
     * @test
     */
    public function baseArgumentAsDateTimeIsConsideredForRelativeDate()
    {
        $this->subject->expects($this->never())->method('renderChildren');
        $actualResult = $this->subject->render('-1 year', 'Y', new \DateTime('2017-01-01'));
        $this->assertEquals('2016', $actualResult);
    }

    /**
     * @test
     */
    public function baseArgumentDoesNotAffectAbsoluteTime()
    {
        $this->subject->expects($this->never())->method('renderChildren');
        $actualResult = $this->subject->render('@1435784732', 'Y', 1485907200); // somewhere in 2017
        $this->assertEquals('2015', $actualResult);
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
        $this->assertEquals($expected, $this->subject->render($date, $format));
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
        $this->assertEquals($expected, $this->subject->render($date, $format));
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

        if (!setlocale(LC_COLLATE, $locale)) {
            $this->markTestSkipped('Locale ' . $locale . ' is not available.');
        }
        $this->setCustomLocale($locale);
        $this->assertEquals($expected, $this->subject->render($timestamp, $format));
    }

    /**
     * @param string $locale
     */
    protected function setCustomLocale($locale)
    {
        setlocale(LC_CTYPE, $locale);
        setlocale(LC_MONETARY, $locale);
        setlocale(LC_TIME, $locale);
    }
}
