<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\UnitDeprecated\Html;

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

use TYPO3\CMS\Core\Html\RteHtmlParser;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class RteHtmlParserTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var RteHtmlParser
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->subject = new RteHtmlParser();
        $this->subject->procOptions = [
            'allowTagsOutside' => 'hr, address',
            'overruleMode' => 'default'
        ];
    }

    /**
     * Data provider for linkWithAtSignCorrectlyTransformedOnWayToRTE
     */
    public static function linkWithAtSignCorrectlyTransformedOnWayToRTEProvider(): array
    {
        return [
            'external url with @ sign' => [
                '<link http://www.example.org/at@sign>link text</link>',
                '<p><a href="http://www.example.org/at@sign">link text</a></p>'
            ],
            'email address with @ sign' => [
                '<link name@example.org - mail "Opens window for sending email">link text</link>',
                '<p><a href="mailto:name@example.org" class="mail" title="Opens window for sending email">link text</a></p>'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider linkWithAtSignCorrectlyTransformedOnWayToRTEProvider
     * @param $content
     * @param $expectedResult
     */
    public function linkWithAtSignCorrectlyTransformedOnWayToRTE(string $content, string $expectedResult): void
    {
        $thisConfig = ['proc.' => $this->subject->procOptions];
        $this->assertEquals($expectedResult, $this->subject->RTE_transform($content, [], 'rte', $thisConfig));
    }
}
